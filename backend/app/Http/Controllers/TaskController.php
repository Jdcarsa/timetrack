<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\UserWorkSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    // ─────────────────────────────────────────
    // GET /api/tasks?week=2024-01-15
    // Devuelve las tareas del empleado para una semana
    // ─────────────────────────────────────────
    public function index(Request $request)
    {
        $user = $request->user();

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $normalTasks = Task::where('user_id', $user->id)
            ->where('is_recurring', false)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $recurringTasks = Task::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->get()
            ->map(function ($task) use ($weekStart) {
                $taskDate = $weekStart->copy()->addDays($task->recur_day);
                $task = $task->replicate();
                $task->date = $taskDate;
                return $task;
            });

        $tasks = $normalTasks
            ->concat($recurringTasks)
            ->sortBy(fn($t) => $t->date->format('Y-m-d') . $t->start_time)
            ->values();

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end'   => $weekEnd->format('Y-m-d'),
            'tasks'      => $tasks->map(fn($t) => $this->formatTask($t)),
        ]);
    }

    // ─────────────────────────────────────────
    // POST /api/tasks
    // Crea una nueva tarea con validación de horario
    // ─────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'date'         => 'required_if:is_recurring,false|nullable|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'is_recurring' => 'boolean',
            'recur_day'    => 'required_if:is_recurring,true|nullable|integer|min:0|max:6',
            'user_id'      => 'nullable|exists:users,id',
            'schedule_id'  => 'nullable|exists:schedules,id',
        ]);
    
        $assignedUserId = $request->filled('user_id')
            ? $request->user_id
            : $request->user()->id;
    
        $date = $request->is_recurring
            ? Carbon::now()->startOfWeek()->addDays($request->recur_day)
            : Carbon::parse($request->date);
    
        // ✅ VALIDACIÓN DE HORARIO LABORAL
        $validationError = $this->validateWorkingHours(
            $assignedUserId, 
            $date, 
            $request->start_time, 
            $request->end_time
        );
        
        if ($validationError) {
            return response()->json([
                'message' => $validationError['message'],
                'errors' => $validationError['errors']
            ], 422);
        }
    
        $task = Task::create([
            'user_id'      => $assignedUserId,
            'title'        => $request->title,
            'description'  => $request->description,
            'date'         => $date,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'status'       => 'pending',
            'is_recurring' => $request->boolean('is_recurring'),
            'recur_day'    => $request->is_recurring ? $request->recur_day : null,
        ]);
    
        if ($request->filled('schedule_id')) {
            $task->schedules()->attach($request->schedule_id);
        }
    
        $task->load('user:id,name');
    
        return response()->json([
            'message' => 'Tarea creada correctamente.',
            'task'    => $this->formatTask($task),
        ], 201);
    }

    // ─────────────────────────────────────────
    // PUT /api/tasks/{task}
    // Edita una tarea existente con validación de horario
    // ─────────────────────────────────────────
    public function update(Request $request, Task $task)
    {
        // Solo el dueño puede editar su tarea
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'date'         => 'sometimes|date',
            'start_time'   => 'sometimes|date_format:H:i',
            'end_time'     => 'sometimes|date_format:H:i|after:start_time',
            'is_recurring' => 'sometimes|boolean',
            'recur_day'    => 'nullable|integer|min:0|max:6',
        ]);

        // Preparar datos para validación
        $date = $request->has('date') 
            ? Carbon::parse($request->date) 
            : $task->date;
            
        $startTime = $request->has('start_time') 
            ? $request->start_time 
            : $task->start_time;
            
        $endTime = $request->has('end_time') 
            ? $request->end_time 
            : $task->end_time;
            
        $isRecurring = $request->has('is_recurring') 
            ? $request->boolean('is_recurring') 
            : $task->is_recurring;
            
        $recurDay = $request->has('recur_day') 
            ? $request->recur_day 
            : $task->recur_day;
            
        // Para tareas recurrentes, recalcular fecha
        if ($isRecurring && $request->has('recur_day')) {
            $date = Carbon::now()->startOfWeek()->addDays($recurDay);
        }
        
        // ✅ VALIDACIÓN DE HORARIO LABORAL (solo si cambian fecha u hora)
        if ($request->has('date') || $request->has('start_time') || $request->has('end_time') || $request->has('recur_day')) {
            $validationError = $this->validateWorkingHours(
                $task->user_id,
                $date,
                $startTime,
                $endTime
            );
            
            if ($validationError) {
                return response()->json([
                    'message' => $validationError['message'],
                    'errors' => $validationError['errors']
                ], 422);
            }
        }

        $task->update($request->only([
            'title', 'description', 'date',
            'start_time', 'end_time', 'is_recurring', 'recur_day'
        ]));

        return response()->json([
            'message' => 'Tarea actualizada.',
            'task'    => $this->formatTask($task->fresh()),
        ]);
    }

    // ─────────────────────────────────────────
    // PATCH /api/tasks/{task}/status
    // Solo cambia el estado de la tarea
    // ─────────────────────────────────────────
    public function updateStatus(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $task->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado actualizado.',
            'task'    => $this->formatTask($task->fresh()),
        ]);
    }

    // ─────────────────────────────────────────
    // DELETE /api/tasks/{task}
    // Elimina una tarea
    // ─────────────────────────────────────────
    public function destroy(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Tarea eliminada.']);
    }

    // ─────────────────────────────────────────
    // GET /api/admin/calendar?week=2024-01-15
    // Solo admin: todas las tareas de todos los empleados
    // ─────────────────────────────────────────
    public function adminCalendar(Request $request)
    {
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $normalTasks = Task::with('user:id,name,email')
            ->where('is_recurring', false)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $recurringTasks = Task::with('user:id,name,email')
            ->where('is_recurring', true)
            ->get()
            ->map(function ($task) use ($weekStart) {
                $taskDate    = $weekStart->copy()->addDays($task->recur_day);
                $task        = $task->replicate(['id']);
                $task->id    = $task->getKey();
                $task->date  = $taskDate;
                return $task;
            });

        $allTasks = $normalTasks->concat($recurringTasks)
            ->sortBy(fn($t) => $t->date->format('Y-m-d') . $t->start_time)
            ->values();

        $grouped = $allTasks->groupBy(fn($t) => $t->date->format('Y-m-d'))
            ->map(fn($dayTasks) => $dayTasks->map(fn($t) => $this->formatTask($t, true)));

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end'   => $weekEnd->format('Y-m-d'),
            'days'       => $grouped,
        ]);
    }

    // ─────────────────────────────────────────
    // Helper: Valida que la tarea esté dentro del horario laboral
    // ─────────────────────────────────────────
    private function validateWorkingHours(int $userId, Carbon $date, string $startTime, string $endTime): ?array
    {
        // Obtener día de la semana (0=lunes, 6=domingo)
        $dayOfWeek = $date->dayOfWeek; // Carbon: 0=domingo, 1=lunes, ..., 6=sábado
        // Convertir a nuestro formato (0=lunes, 6=domingo)
        $dayOfWeekIndex = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;
        
        // Obtener horario del usuario para ese día
        $workSchedule = UserWorkSchedule::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeekIndex)
            ->first();
        
        // Verificar si es día laboral
        if (!$workSchedule || !$workSchedule->is_working) {
            $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            return [
                'message' => 'No puedes programar tareas en un día no laboral.',
                'errors' => ['date' => ["El {$dayNames[$dayOfWeekIndex]} no es laboral para este empleado."]]
            ];
        }
        
        // Validar que la tarea esté dentro del horario laboral
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $workStart = Carbon::parse($workSchedule->start_time);
        $workEnd = Carbon::parse($workSchedule->end_time);
        
        // Restar el tiempo de descanso del horario laboral
        $breakMinutes = $workSchedule->break_minutes ?? 0;
        $workEndWithBreak = $workEnd->copy()->subMinutes($breakMinutes);
        
        if ($start->lt($workStart)) {
            return [
                'message' => 'La tarea comienza antes del horario laboral.',
                'errors' => ['start_time' => ["El horario laboral comienza a las {$workSchedule->start_time}"]]
            ];
        }
        
        if ($end->gt($workEndWithBreak)) {
            return [
                'message' => 'La tarea termina después del horario laboral.',
                'errors' => ['end_time' => ["El horario laboral termina a las {$workEndWithBreak->format('H:i')} (incluyendo {$breakMinutes} min de descanso)"]]
            ];
        }
        
        // Validar que no se solape con otra tarea existente
        $existingTask = Task::where('user_id', $userId)
            ->where('date', $date->format('Y-m-d'))
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->exists();
        
        if ($existingTask) {
            return [
                'message' => 'Ya existe una tarea programada en ese horario.',
                'errors' => ['time' => ['Ya hay una tarea en ese horario.']]
            ];
        }
        
        return null; // No hay error
    }

    // ─────────────────────────────────────────
    // Helper: formatea una tarea para la respuesta JSON
    // ─────────────────────────────────────────
    private function formatTask(Task $task, bool $withUser = false): array
    {
        $task->loadMissing('schedules:id,title,project_id');

        $schedule = $task->schedules->first();

        $data = [
            'id'           => $task->id,
            'title'        => $task->title,
            'description'  => $task->description,
            'date'         => $task->date?->format('Y-m-d'),
            'start_time'   => $task->start_time,
            'end_time'     => $task->end_time,
            'status'       => $task->status,
            'status_label' => $task->status_label,
            'is_recurring' => $task->is_recurring,
            'recur_day'    => $task->recur_day,
            'day_name'     => $task->day_name,
            'schedule'     => $schedule ? [
                'id'    => $schedule->id,
                'title' => $schedule->title,
            ] : null,
        ];

        if ($withUser && $task->relationLoaded('user')) {
            $data['user'] = [
                'id'    => $task->user->id,
                'name'  => $task->user->name,
                'email' => $task->user->email,
            ];
        }

        return $data;
    }
}