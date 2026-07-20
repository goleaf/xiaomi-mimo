<?php

namespace App\Actions;

use App\Data\WorkspaceInvitationIssue;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResendWorkspaceInvitation
{
    public function handle(WorkspaceInvitation $invitation, User $inviter): WorkspaceInvitationIssue
    {
        return DB::transaction(function () use ($invitation, $inviter): WorkspaceInvitationIssue {
            $token = Str::random(64);
            $updated = WorkspaceInvitation::query()
                ->whereKey($invitation->id)
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->whereHas('workspace.memberships', fn ($query) => $query
                    ->where('user_id', $inviter->id)
                    ->whereIn('role', [WorkspaceRole::Owner, WorkspaceRole::Admin]))
                ->update([
                    'invited_by' => $inviter->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addDays(7),
                ]);

            if ($updated !== 1) {
                throw new AuthorizationException;
            }

            $invitation = WorkspaceInvitation::query()
                ->with('workspace')
                ->findOrFail($invitation->id);

            return new WorkspaceInvitationIssue($invitation, $token);
        }, 5);
    }
}
