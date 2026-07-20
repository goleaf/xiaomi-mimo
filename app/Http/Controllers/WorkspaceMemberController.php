<?php

namespace App\Http\Controllers;

use App\Actions\RemoveFromWorkspace;
use App\Actions\UpdateWorkspaceMemberRole;
use App\Http\Requests\RemoveWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRoleRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\Workspace;
use App\Queries\WorkspaceManagementQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkspaceMemberController extends Controller
{
    public function index(
        Request $request,
        Workspace $workspace,
        WorkspaceManagementQuery $query,
    ): AnonymousResourceCollection {
        $this->authorize('view', $workspace);

        return WorkspaceMemberResource::collection($query->members($workspace));
    }

    public function update(
        UpdateWorkspaceMemberRoleRequest $request,
        Workspace $workspace,
        UpdateWorkspaceMemberRole $action,
    ): JsonResponse|RedirectResponse {
        $membership = $action->handle($request->membership(), $request->user(), $request->role());

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['member' => new WorkspaceMemberResource($membership)]);
        }

        return to_route('workspaces.members', $workspace);
    }

    public function destroy(
        RemoveWorkspaceMemberRequest $request,
        Workspace $workspace,
        RemoveFromWorkspace $action,
    ): JsonResponse|RedirectResponse {
        $action->handle($request->membership(), $request->user());

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(null, 204);
        }

        return to_route('workspaces.members', $workspace);
    }
}
