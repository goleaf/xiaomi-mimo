<?php

namespace App\Http\Controllers;

use App\Actions\TransferWorkspaceOwnership;
use App\Http\Requests\TransferWorkspaceOwnershipRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceOwnershipController extends Controller
{
    public function __invoke(
        TransferWorkspaceOwnershipRequest $request,
        Workspace $workspace,
        TransferWorkspaceOwnership $action,
    ): RedirectResponse {
        $action->handle($workspace, $request->user(), $request->newOwner());

        return to_route('workspaces.members', $workspace);
    }
}
