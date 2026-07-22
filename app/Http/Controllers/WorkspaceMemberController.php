<?php

namespace App\Http\Controllers;

use App\Actions\RemoveFromWorkspace;
use App\Actions\UpdateWorkspaceMemberRole;
use App\Http\Requests\RemoveWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRoleRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceMemberController extends Controller
{
    public function update(
        UpdateWorkspaceMemberRoleRequest $request,
        Workspace $workspace,
        UpdateWorkspaceMemberRole $action,
    ): RedirectResponse {
        $action->handle($request->membership(), $request->user(), $request->role());

        return to_route('workspaces.members', $workspace);
    }

    public function destroy(
        RemoveWorkspaceMemberRequest $request,
        Workspace $workspace,
        RemoveFromWorkspace $action,
    ): RedirectResponse {
        $action->handle($request->membership(), $request->user());

        return to_route('workspaces.members', $workspace);
    }
}
