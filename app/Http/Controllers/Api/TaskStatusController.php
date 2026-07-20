<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\TaskStatusController as WebTaskStatusController;
use App\Http\Resources\TaskStatusResource;
use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskStatusController extends WebTaskStatusController
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $statuses = TaskStatusResource::collection(
            $workspace->taskStatuses()
                ->with('workspace')
                ->withCount(['allTodos as todos_count'])
                ->ordered()
                ->limit(TaskStatus::MAX_PER_WORKSPACE)
                ->get(),
        )->resolve($request);

        return response()->json(['data' => $statuses]);
    }
}
