<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->checkAccess($request->user(), $project);

        $schedules = $project->schedules()
            ->with(['creator:id,name', 'tasks.user:id,name'])
            ->orderBy('week_start')
            ->get();

        return response()->json($schedules->map(fn($s) => $this->formatSchedule($s)));
    }

    public function store(Request $request, Project $project)
    {
        $this->checkAccess($request->user(), $project);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'week_start'  => 'required|date',
        ]);

        $weekStart = \Carbon\Carbon::parse($request->week_start)
            ->startOfWeek(\Carbon\Carbon::MONDAY)
            ->format('Y-m-d');

        $schedule = Schedule::create([
            'project_id'  => $project->id,
            'created_by'  => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'week_start'  => $weekStart,
        ]);

        $schedule->load(['creator:id,name', 'tasks.user:id,name']);

        return response()->json([
            'message'  => 'Cronograma creado correctamente.',
            'schedule' => $this->formatSchedule($schedule),
        ], 201);
    }

    public function update(Request $request, Project $project, Schedule $schedule)
    {
        $this->checkAccess($request->user(), $project);

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'week_start'  => 'sometimes|date',
        ]);

        $schedule->update($request->only(['title', 'description', 'week_start']));
        $schedule->load(['creator:id,name', 'tasks.user:id,name']);

        return response()->json([
            'message'  => 'Cronograma actualizado.',
            'schedule' => $this->formatSchedule($schedule),
        ]);
    }

    public function destroy(Request $request, Project $project, Schedule $schedule)
    {
        $this->checkAccess($request->user(), $project);
        $schedule->delete();
        return response()->json(['message' => 'Cronograma eliminado.']);
    }

    public function addTask(Request $request, Project $project, Schedule $schedule)
    {
        $this->checkAccess($request->user(), $project);

        $request->validate([
            'task_id' => 'required|exists:tasks,id',
        ]);

        $task = Task::findOrFail($request->task_id);

        if ($schedule->tasks()->where('task_id', $task->id)->exists()) {
            return response()->json(['message' => 'La tarea ya esta en el cronograma.'], 422);
        }

        $schedule->tasks()->attach($task->id);
        $schedule->load(['creator:id,name', 'tasks.user:id,name']);

        return response()->json([
            'message'  => 'Tarea agregada al cronograma.',
            'schedule' => $this->formatSchedule($schedule),
        ]);
    }

    public function removeTask(Request $request, Project $project, Schedule $schedule, Task $task)
    {
        $this->checkAccess($request->user(), $project);
        $schedule->tasks()->detach($task->id);

        return response()->json(['message' => 'Tarea removida del cronograma.']);
    }

    public function availableTasks(Request $request, Project $project, Schedule $schedule)
    {
        $this->checkAccess($request->user(), $project);

        $user = $request->user();

        $weekEnd = \Carbon\Carbon::parse($schedule->week_start)
            ->endOfWeek(\Carbon\Carbon::SUNDAY)
            ->format('Y-m-d');

        $assignedTaskIds = $schedule->tasks()->pluck('task_id');

        if ($user->isAdmin()) {
            $memberIds = $project->members()->pluck('user_id');
            $tasks = Task::whereIn('user_id', $memberIds)
                ->whereBetween('date', [$schedule->week_start, $weekEnd])
                ->whereNotIn('id', $assignedTaskIds)
                ->with('user:id,name')
                ->get();
        } else {
            $tasks = Task::where('user_id', $user->id)
                ->whereBetween('date', [$schedule->week_start, $weekEnd])
                ->whereNotIn('id', $assignedTaskIds)
                ->with('user:id,name')
                ->get();
        }

        return response()->json($tasks->map(fn($t) => [
            'id'         => $t->id,
            'title'      => $t->title,
            'date'       => $t->date?->format('Y-m-d'),
            'start_time' => $t->start_time,
            'end_time'   => $t->end_time,
            'status'     => $t->status,
            'user'       => $t->user?->only(['id', 'name']),
        ]));
    }

    private function checkAccess($user, Project $project): void
    {
        if ($user->isAdmin()) return;

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            abort(403, 'No tienes acceso a este proyecto.');
        }
    }

    private function formatSchedule(Schedule $schedule): array
    {
        return [
            'id'          => $schedule->id,
            'project_id'  => $schedule->project_id,
            'title'       => $schedule->title,
            'description' => $schedule->description,
            'week_start'  => $schedule->week_start?->format('Y-m-d'),
            'created_by'  => $schedule->creator?->only(['id', 'name']),
            'tasks'       => $schedule->tasks->map(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'description' => $t->description,
                'date'        => $t->date?->format('Y-m-d'),
                'start_time'  => $t->start_time,
                'end_time'    => $t->end_time,
                'status'      => $t->status,
                'user'        => $t->user?->only(['id', 'name']),
            ])->values(),
        ];
    }
}