<?php

namespace App\Http\Controllers;

use App\Actions\ManageTaskStatus;
use App\Http\Requests\DeleteTaskStatusRequest;
use App\Http\Requests\ManageTaskStatusRequest;
use App\Http\Requests\ReorderTaskStatusesRequest;
use App\Http\Requests\StoreTaskStatusRequest;
use App\Http\Resources\TaskStatusResource;
use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskStatusController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        return response()->json([
            'statuses' => TaskStatusResource::collection($this->statuses($workspace))->resolve($request),
        ]);
    }

    public function store(
        StoreTaskStatusRequest $request,
        Workspace $workspace,
        ManageTaskStatus $action,
    ): JsonResponse {
        $status = $action->create($workspace, $request->definitionData());

        return response()->json(['status' => new TaskStatusResource($status)], 201);
    }

    public function update(
        StoreTaskStatusRequest $request,
        Workspace $workspace,
        TaskStatus $taskStatus,
        ManageTaskStatus $action,
    ): JsonResponse {
        $status = $action->update($taskStatus, $request->definitionData());

        return response()->json(['status' => new TaskStatusResource($status)]);
    }

    public function manage(
        ManageTaskStatusRequest $request,
        Workspace $workspace,
        TaskStatus $taskStatus,
        ManageTaskStatus $action,
    ): JsonResponse {
        $status = match ($request->operation()) {
            'archive' => $action->archive($taskStatus),
            'restore' => $action->restore($taskStatus),
            'set_default' => $action->setDefault($taskStatus),
            'set_completion_target' => $action->setCompletionTarget($taskStatus),
            default => throw ValidationException::withMessages([
                'operation' => __('validation.in', ['attribute' => 'operation']),
            ]),
        };

        return response()->json(['status' => new TaskStatusResource($status)]);
    }

    public function reorder(
        ReorderTaskStatusesRequest $request,
        Workspace $workspace,
        ManageTaskStatus $action,
    ): JsonResponse {
        $action->reorder($workspace, $request->ids());

        return response()->json(null, 204);
    }

    public function destroy(
        DeleteTaskStatusRequest $request,
        Workspace $workspace,
        TaskStatus $taskStatus,
        ManageTaskStatus $action,
    ): JsonResponse {
        $action->delete($taskStatus, $request->replacement());

        return response()->json(null, 204);
    }

    /** @return Collection<int, TaskStatus> */
    private function statuses(Workspace $workspace): Collection
    {
        return $workspace->taskStatuses()
            ->with('workspace')
            ->withCount(['allTodos as todos_count'])
            ->ordered()
            ->limit(TaskStatus::MAX_PER_WORKSPACE)
            ->get();
    }
}
