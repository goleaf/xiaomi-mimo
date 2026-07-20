<?php

namespace App\Http\Controllers;

use App\Actions\CancelWorkspaceInvitation;
use App\Actions\InviteToWorkspace;
use App\Actions\ResendWorkspaceInvitation;
use App\Http\Requests\CancelWorkspaceInvitationRequest;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\ResendWorkspaceInvitationRequest;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    ): JsonResponse|RedirectResponse {
        $issue = $action->handle($workspace, $request->user(), $request->email(), $request->role());
        Notification::route('mail', $issue->invitation->email)
            ->notify(new WorkspaceInvitationNotification($issue->invitation, $issue->token));

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'invitation' => new WorkspaceInvitationResource($issue->invitation),
            ], 201);
        }

        return to_route('workspaces.members', $workspace);
    }

    public function resend(
        ResendWorkspaceInvitationRequest $request,
        Workspace $workspace,
        ResendWorkspaceInvitation $action,
    ): JsonResponse|RedirectResponse {
        $issue = $action->handle($request->invitation(), $request->user());
        Notification::route('mail', $issue->invitation->email)
            ->notify(new WorkspaceInvitationNotification($issue->invitation, $issue->token));

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'invitation' => new WorkspaceInvitationResource($issue->invitation),
            ]);
        }

        return to_route('workspaces.members', $workspace);
    }

    public function destroy(
        CancelWorkspaceInvitationRequest $request,
        Workspace $workspace,
        CancelWorkspaceInvitation $action,
    ): JsonResponse|RedirectResponse {
        $action->handle($request->invitation(), $request->user());

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(null, 204);
        }

        return to_route('workspaces.members', $workspace);
    }
}
