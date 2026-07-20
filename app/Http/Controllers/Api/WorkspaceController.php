<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateWorkspace;
use App\Actions\DeleteWorkspace;
use App\Actions\DuplicateWorkspace;
use App\Actions\UpdateWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateWorkspaceRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkspaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaces = $request->user()->workspaces()
            ->withCount(['members', 'projects', 'todos'])
            ->get();

        return WorkspaceResource::collection($workspaces);
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($request->workspaceData(), $request->user());

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos'])),
        ], 201);
    }

    public function show(Workspace $workspace): WorkspaceResource
    {
        $this->authorize('view', $workspace);

        return new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos']));
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($workspace, $request->validated());

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos'])),
        ]);
    }

    public function duplicate(
        DuplicateWorkspaceRequest $request,
        Workspace $workspace,
        DuplicateWorkspace $action,
    ): JsonResponse {
        $copy = $action->handle($workspace, $request->user(), $request->workspaceName());

        return response()->json([
            'workspace' => new WorkspaceResource($copy->loadCount(['members', 'projects', 'todos'])),
        ], 201);
    }

    public function destroy(Workspace $workspace, DeleteWorkspace $action): JsonResponse
    {
        $this->authorize('delete', $workspace);
        $action->handle($workspace);

        return response()->json(null, 204);
    }
}
