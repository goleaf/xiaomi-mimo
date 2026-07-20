<?php

namespace App\Http\Controllers;

use App\Actions\AcceptWorkspaceInvitation;
use App\Http\Requests\AcceptWorkspaceInvitationRequest;
use Illuminate\Http\RedirectResponse;

class WorkspaceInvitationAcceptanceController extends Controller
{
    public function __invoke(
        AcceptWorkspaceInvitationRequest $request,
        AcceptWorkspaceInvitation $action,
    ): RedirectResponse {
        $membership = $action->handle($request->invitation(), $request->user(), $request->token());
        $request->session()->put('current_workspace_id', $membership->workspace_id);

        return to_route('workspaces.members', $membership->workspace_id);
    }
}
