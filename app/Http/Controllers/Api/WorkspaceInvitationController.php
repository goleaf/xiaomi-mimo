<?php

namespace App\Http\Controllers\Api;

use App\Actions\CancelWorkspaceInvitation;
use App\Actions\InviteToWorkspace;
use App\Actions\ResendWorkspaceInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelWorkspaceInvitationRequest;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\ResendWorkspaceInvitationRequest;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;

class WorkspaceInvitationController extends Controller
{
    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        $this->authorize('manageMembers', $workspace);

        return WorkspaceInvitationResource::collection(
            $workspace->invitations()
                ->with('workspace')
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->latest()
                ->get(),
        );
    }

    public function store(
        InviteMemberRequest $request,
        Workspace $workspace,
        InviteToWorkspace $action,
    ): JsonResponse {
        $issue = $action->handle($workspace, $request->user(), $request->email(), $request->role());
        Notification::route('mail', $issue->invitation->email)
            ->notify(new WorkspaceInvitationNotification($issue->invitation, $issue->token));

        return response()->json([
            'invitation' => new WorkspaceInvitationResource($issue->invitation),
        ], 201);
    }

    public function resend(
        ResendWorkspaceInvitationRequest $request,
        Workspace $workspace,
        ResendWorkspaceInvitation $action,
    ): JsonResponse {
        $issue = $action->handle($request->invitation(), $request->user());
        Notification::route('mail', $issue->invitation->email)
            ->notify(new WorkspaceInvitationNotification($issue->invitation, $issue->token));

        return response()->json([
            'invitation' => new WorkspaceInvitationResource($issue->invitation),
        ]);
    }

    public function destroy(
        CancelWorkspaceInvitationRequest $request,
        Workspace $workspace,
        CancelWorkspaceInvitation $action,
    ): JsonResponse {
        $action->handle($request->invitation(), $request->user());

        return response()->json(null, 204);
    }
}
