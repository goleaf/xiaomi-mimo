<?php

namespace App\Http\Controllers\Api;

use App\Actions\RemoveFromWorkspace;
use App\Actions\UpdateWorkspaceMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRoleRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\Workspace;
use App\Queries\WorkspaceManagementQuery;
use Illuminate\Http\JsonResponse;
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
    ): JsonResponse {
        $membership = $action->handle($request->membership(), $request->user(), $request->role());

        return response()->json(['member' => new WorkspaceMemberResource($membership)]);
    }

    public function destroy(
        RemoveWorkspaceMemberRequest $request,
        Workspace $workspace,
        RemoveFromWorkspace $action,
    ): JsonResponse {
        $action->handle($request->membership(), $request->user());

        return response()->json(null, 204);
    }
}
