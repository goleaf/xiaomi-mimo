<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\TaskPriorityController as WebTaskPriorityController;
use App\Http\Resources\TaskPriorityResource;
use App\Models\TaskPriority;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskPriorityController extends WebTaskPriorityController
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $priorities = TaskPriorityResource::collection(
            $workspace->taskPriorities()
                ->with('workspace')
                ->withCount(['allTodos as todos_count'])
                ->ordered()
                ->limit(TaskPriority::MAX_PER_WORKSPACE)
                ->get(),
        )->resolve($request);

        return response()->json(['data' => $priorities]);
    }
}
