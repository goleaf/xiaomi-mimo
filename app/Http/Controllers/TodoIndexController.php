<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskPriorityResource;
use App\Http\Resources\TaskStatusResource;
use App\Http\Resources\TodoResource;
use App\Models\User;
use App\Queries\CurrentWorkspaceQuery;
use App\Queries\TodoIndexQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoIndexController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentWorkspaceQuery $currentWorkspaceQuery,
        TodoIndexQuery $todoIndexQuery,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $workspace = $currentWorkspaceQuery->forUser(
            $user,
            $request->session()->get('current_workspace_id'),
        );

        if (! $workspace) {
            return Inertia::render('tasks/Index', [
                'todos' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 50,
                ],
                'filters' => [],
                'projects' => ['data' => []],
                'workspace' => ['id' => ''],
                'taskDefinitions' => ['statuses' => [], 'priorities' => []],
            ]);
        }

        $this->authorize('view', $workspace);

        /** @var array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null} $filters */
        $filters = $request->only([
            'search', 'project_id', 'status', 'priority', 'assigned_to',
            'label_id', 'tag_id', 'is_pinned', 'is_favorite',
            'due_date_from', 'due_date_to', 'overdue', 'completed_today',
        ]);
        $todos = $todoIndexQuery->todos(
            $workspace,
            $filters,
            $request->string('sort')->toString() ?: null,
            $request->string('direction')->toString() ?: null,
            min(max($request->integer('per_page', 50), 1), 100),
        );

        return Inertia::render('tasks/Index', [
            'todos' => TodoResource::collection($todos),
            'filters' => $request->only(['search', 'project_id', 'status', 'priority']),
            'projects' => ProjectResource::collection($todoIndexQuery->projects($workspace)),
            'workspace' => ['id' => $workspace->id],
            'taskDefinitions' => [
                'statuses' => TaskStatusResource::collection(
                    $todoIndexQuery->statuses($workspace),
                )->resolve($request),
                'priorities' => TaskPriorityResource::collection(
                    $todoIndexQuery->priorities($workspace),
                )->resolve($request),
            ],
        ]);
    }
}
