<?php

namespace App\Http\Controllers;

use App\Actions\ManageTaskPriority;
use App\Http\Requests\DeleteTaskPriorityRequest;
use App\Http\Requests\ManageTaskPriorityRequest;
use App\Http\Requests\ReorderTaskPrioritiesRequest;
use App\Http\Requests\StoreTaskPriorityRequest;
use App\Http\Resources\TaskPriorityResource;
use App\Models\TaskPriority;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskPriorityController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return response()->json([
            'priorities' => TaskPriorityResource::collection($this->priorities($workspace))->resolve($request),
        ]);
    }

    public function store(
        StoreTaskPriorityRequest $request,
        Workspace $workspace,
        ManageTaskPriority $action,
    ): JsonResponse {
        $priority = $action->create($workspace, $request->definitionData());

        return response()->json(['priority' => new TaskPriorityResource($priority)], 201);
    }

    public function update(
        StoreTaskPriorityRequest $request,
        Workspace $workspace,
        TaskPriority $taskPriority,
        ManageTaskPriority $action,
    ): JsonResponse {
        $priority = $action->update($taskPriority, $request->definitionData());

        return response()->json(['priority' => new TaskPriorityResource($priority)]);
    }

    public function manage(
        ManageTaskPriorityRequest $request,
        Workspace $workspace,
        TaskPriority $taskPriority,
        ManageTaskPriority $action,
    ): JsonResponse {
        $priority = match ($request->operation()) {
            'archive' => $action->archive($taskPriority),
            'restore' => $action->restore($taskPriority),
            'set_default' => $action->setDefault($taskPriority),
            default => throw ValidationException::withMessages([
                'operation' => __('validation.in', ['attribute' => 'operation']),
            ]),
        };

        return response()->json(['priority' => new TaskPriorityResource($priority)]);
    }

    public function reorder(
        ReorderTaskPrioritiesRequest $request,
        Workspace $workspace,
        ManageTaskPriority $action,
    ): JsonResponse {
        $action->reorder($workspace, $request->ids());

        return response()->json(null, 204);
    }

    public function destroy(
        DeleteTaskPriorityRequest $request,
        Workspace $workspace,
        TaskPriority $taskPriority,
        ManageTaskPriority $action,
    ): JsonResponse {
        $action->delete($taskPriority, $request->replacement());

        return response()->json(null, 204);
    }

    /** @return Collection<int, TaskPriority> */
    private function priorities(Workspace $workspace): Collection
    {
        return $workspace->taskPriorities()
            ->with('workspace')
            ->withCount(['allTodos as todos_count'])
            ->ordered()
            ->limit(TaskPriority::MAX_PER_WORKSPACE)
            ->get();
    }
}
