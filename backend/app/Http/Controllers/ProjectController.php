<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $projects = Project::with(['creator:id,name', 'members:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $projects = $user->projects()
                ->with(['creator:id,name', 'members:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($projects->map(fn($p) => $this->formatProject($p)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'member_ids'  => 'array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        $project = Project::create([
            'created_by'  => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'status'      => 'in_progress',
        ]);

        if ($request->filled('member_ids')) {
            $project->members()->sync($request->member_ids);
        }

        $project->load(['creator:id,name', 'members:id,name,email']);

        return response()->json([
            'message' => 'Proyecto creado correctamente.',
            'project' => $this->formatProject($project),
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        $this->checkAccess($request->user(), $project);

        $project->load([
            'creator:id,name',
            'members:id,name,email',
            'schedules.creator:id,name',
            'schedules.tasks.user:id,name',
        ]);

        return response()->json($this->formatProject($project, true));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'sometimes|date|after_or_equal:start_date',
            'status'      => 'sometimes|in:in_progress,completed',
            'member_ids'  => 'array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        $project->update($request->only([
            'title', 'description', 'start_date', 'end_date', 'status'
        ]));

        if ($request->has('member_ids')) {
            $project->members()->sync($request->member_ids);
        }

        $project->load(['creator:id,name', 'members:id,name,email']);

        return response()->json([
            'message' => 'Proyecto actualizado.',
            'project' => $this->formatProject($project),
        ]);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Proyecto eliminado.']);
    }

    private function checkAccess($user, Project $project): void
    {
        if ($user->isAdmin()) return;

        $isMember = $project->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            abort(403, 'No tienes acceso a este proyecto.');
        }
    }

    private function formatProject(Project $project, bool $withSchedules = false): array
    {
        $data = [
            'id'           => $project->id,
            'title'        => $project->title,
            'description'  => $project->description,
            'start_date'   => $project->start_date?->format('Y-m-d'),
            'end_date'     => $project->end_date?->format('Y-m-d'),
            'status'       => $project->status,
            'status_label' => $project->status_label,
            'created_by'   => $project->creator?->only(['id', 'name']),
            'members'      => $project->members->map->only(['id', 'name', 'email'])->values(),
            'schedules_count' => $project->schedules_count ?? $project->schedules->count(),
        ];

        if ($withSchedules) {
            $data['schedules'] = $project->schedules->map(fn($s) => [
                'id'          => $s->id,
                'title'       => $s->title,
                'description' => $s->description,
                'week_start'  => $s->week_start?->format('Y-m-d'),
                'created_by'  => $s->creator?->only(['id', 'name']),
                'tasks'       => $s->tasks->map(fn($t) => [
                    'id'          => $t->id,
                    'title'       => $t->title,
                    'description' => $t->description,
                    'date'        => $t->date?->format('Y-m-d'),
                    'start_time'  => $t->start_time,
                    'end_time'    => $t->end_time,
                    'status'      => $t->status,
                    'user'        => $t->user?->only(['id', 'name']),
                ])->values(),
            ])->values();
        }

        return $data;
    }
}