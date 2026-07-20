<?php

namespace App\Actions;

use App\Data\WorkspaceInvitationIssue;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteToWorkspace
{
    public function handle(
        Workspace $workspace,
        User $inviter,
        string $email,
        WorkspaceRole $role,
    ): WorkspaceInvitationIssue {
        $normalizedEmail = Str::lower($email);

        return DB::transaction(function () use ($workspace, $inviter, $normalizedEmail, $role): WorkspaceInvitationIssue {
            $canInvite = WorkspaceMember::query()
                ->where('workspace_id', $workspace->id)
                ->where('user_id', $inviter->id)
                ->whereIn('role', [WorkspaceRole::Owner, WorkspaceRole::Admin])
                ->exists();

            if (! $canInvite) {
                throw new AuthorizationException;
            }

            if ($workspace->members()->where('email', $normalizedEmail)->exists()) {
                throw ValidationException::withMessages([
                    'email' => [__('validation.unique', ['attribute' => 'email'])],
                ]);
            }

            $token = Str::random(64);
            $invitation = $workspace->invitations()->updateOrCreate(
                ['email' => $normalizedEmail],
                [
                    'invited_by' => $inviter->id,
                    'role' => $role,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addDays(7),
                    'accepted_at' => null,
                    'cancelled_at' => null,
                ],
            );
            $invitation->setRelation('workspace', $workspace);

            return new WorkspaceInvitationIssue($invitation, $token);
        }, 5);
    }
}
