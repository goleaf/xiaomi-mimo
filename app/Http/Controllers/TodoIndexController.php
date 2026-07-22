<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoIndexRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskPriorityResource;
use App\Http\Resources\TaskStatusResource;
use App\Http\Resources\TodoResource;
use App\Models\User;
use App\Queries\CurrentWorkspaceQuery;
use App\Queries\TodoIndexQuery;
use Inertia\Inertia;
use Inertia\Response;

class TodoIndexController extends Controller
{
    public function __invoke(
        TodoIndexRequest $request,
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
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0],
                'projects' => ['data' => []],
                'workspace' => ['id' => ''],
                'taskDefinitions' => ['statuses' => [], 'priorities' => []],
            ]);
        }

        $this->authorize('view', $workspace);

        $filters = $request->filters();
        $todos = $todoIndexQuery->todos(
            $workspace,
            $filters,
            $request->sort(),
            $request->direction(),
            $request->perPage(),
        );

        return Inertia::render('tasks/Index', [
            'todos' => TodoResource::collection($todos),
            'filters' => $request->state(),
            'stats' => $todoIndexQuery->stats($workspace, $filters),
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
