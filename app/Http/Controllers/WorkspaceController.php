<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkspace;
use App\Actions\DeleteWorkspace;
use App\Actions\InviteToWorkspace;
use App\Actions\RemoveFromWorkspace;
use App\Actions\UpdateWorkspace;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaces = $request->user()->workspaces()->withCount(['projects', 'todos'])->get();

        return Inertia::render('workspaces/Index', [
            'workspaces' => WorkspaceResource::collection($workspaces),
        ]);
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($request->validated(), $request->user());

        return response()->json([
            'workspace' => new WorkspaceResource($workspace->loadCount(['projects', 'todos'])),
        ], 201);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $action): JsonResponse
    {
        $workspace = $action->handle($workspace, $request->validated());

        return response()->json(['workspace' => new WorkspaceResource($workspace)]);
    }

    public function destroy(Workspace $workspace, DeleteWorkspace $action): JsonResponse
    {
        $this->authorize('delete', $workspace);
        $action->handle($workspace);

        return response()->json(null, 204);
    }

    public function switch(Request $request, Workspace $workspace): JsonResponse
    {
        abort_unless($workspace->hasMember($request->user()), 403);

        $request->session()->put('current_workspace_id', $workspace->id);

        return response()->json(['workspace' => new WorkspaceResource($workspace)]);
    }

    public function invite(InviteMemberRequest $request, Workspace $workspace, InviteToWorkspace $action): JsonResponse
    {
        $member = $action->handle($workspace, $request->email, $request->role ?? 'member');

        return response()->json(['member' => $member->load('user')], 201);
    }

    public function removeMember(Workspace $workspace, string $userId, RemoveFromWorkspace $action): JsonResponse
    {
        abort_unless($workspace->hasMember($request->user()), 403);

        $action->handle($workspace, $userId);

        return response()->json(null, 204);
    }
}
