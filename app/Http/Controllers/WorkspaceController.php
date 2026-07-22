<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkspace;
use App\Actions\DeleteWorkspace;
use App\Actions\DuplicateWorkspace;
use App\Actions\UpdateWorkspace;
use App\Http\Requests\DuplicateWorkspaceRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\CurrentWorkspaceQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaces = $request->user()->workspaces()
            ->withCount(['members', 'projects', 'todos'])
            ->get();
        $selectedWorkspaceId = $request->session()->get('current_workspace_id');
        $currentWorkspace = is_string($selectedWorkspaceId)
            ? $workspaces->firstWhere('id', $selectedWorkspaceId)
            : null;

        if (! $currentWorkspace) {
            $currentWorkspace = $workspaces->first();

            if ($currentWorkspace) {
                $request->session()->put('current_workspace_id', $currentWorkspace->id);
            } else {
                $request->session()->forget('current_workspace_id');
            }
        }

        return Inertia::render('workspaces/Index', [
            'workspaces' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($request->workspaceData(), $request->user());

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos'])),
        ], 201);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($workspace, $request->validated());

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos'])),
        ]);
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        DeleteWorkspace $action,
        CurrentWorkspaceQuery $currentWorkspaceQuery,
    ): JsonResponse {
        $this->authorize('delete', $workspace);
        $deletedCurrentWorkspace = $request->session()->get('current_workspace_id') === $workspace->id;
        $action->handle($workspace);

        if ($deletedCurrentWorkspace) {
            $user = $request->user();
            abort_unless($user instanceof User, 403);
            $fallbackWorkspace = $currentWorkspaceQuery->forUser($user);

            if ($fallbackWorkspace) {
                $request->session()->put('current_workspace_id', $fallbackWorkspace->id);
            } else {
                $request->session()->forget('current_workspace_id');
            }
        }

        return response()->json(null, 204);
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

    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        abort_unless($workspace->hasMember($request->user()), 403);

        $request->session()->put('current_workspace_id', $workspace->id);

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['members', 'projects', 'todos'])),
        ]);
    }
}
