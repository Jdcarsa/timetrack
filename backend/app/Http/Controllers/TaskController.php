<?php

namespace App\Http\Controllers;

use App\Models\Task;
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

        // Si no mandan semana, usamos la semana actual
        // Carbon::parse()->startOfWeek() da el lunes de esa semana
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->week)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // 1. Tareas normales (no recurrentes) en ese rango de fechas
        $normalTasks = Task::where('user_id', $user->id)
            ->where('is_recurring', false)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        // 2. Tareas recurrentes del empleado
        // Las generamos virtualmente para cada día de la semana
        $recurringTasks = Task::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->get()
            ->map(function ($task) use ($weekStart) {
                // Calculamos la fecha real de esta semana para ese día
                // recur_day: 0=lunes, 1=martes, etc.
                $taskDate = $weekStart->copy()->addDays($task->recur_day);

                // Clonamos la tarea con la fecha de esta semana
                $task = $task->replicate();
                $task->date = $taskDate;
                return $task;
            });

        // 3. Unimos ambas colecciones y ordenamos por fecha y hora
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
    // Crea una nueva tarea
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
            ? \Carbon\Carbon::now()->startOfWeek()->addDays($request->recur_day)
            : \Carbon\Carbon::parse($request->date);
    
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
    // Edita una tarea existente
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
            'end_time'     => 'sometimes|date_format:H:i',
            'is_recurring' => 'sometimes|boolean',
            'recur_day'    => 'nullable|integer|min:0|max:6',
        ]);

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

        // Tareas normales de todos los empleados esa semana
        $normalTasks = Task::with('user:id,name,email')
            ->where('is_recurring', false)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        // Tareas recurrentes de todos los empleados
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

        // Agrupamos por fecha para que Vue pueda pintar el calendario fácilmente
        $grouped = $allTasks->groupBy(fn($t) => $t->date->format('Y-m-d'))
            ->map(fn($dayTasks) => $dayTasks->map(fn($t) => $this->formatTask($t, true)));

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end'   => $weekEnd->format('Y-m-d'),
            'days'       => $grouped,
        ]);
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