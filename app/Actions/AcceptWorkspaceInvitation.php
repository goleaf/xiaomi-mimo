<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcceptWorkspaceInvitation
{
    public function handle(WorkspaceInvitation $invitation, User $user, string $token): WorkspaceMember
    {
        return DB::transaction(function () use ($invitation, $user, $token): WorkspaceMember {
            $acceptedAt = now();
            $updated = WorkspaceInvitation::query()
                ->whereKey($invitation->id)
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->where('expires_at', '>', $acceptedAt)
                ->where('email', Str::lower($user->email))
                ->where('token_hash', hash('sha256', $token))
                ->update([
                    'accepted_at' => $acceptedAt,
                    'token_hash' => hash('sha256', Str::random(64)),
                ]);

            if ($updated !== 1) {
                throw new AuthorizationException;
            }

            $membership = WorkspaceMember::query()->firstOrCreate(
                [
                    'workspace_id' => $invitation->workspace_id,
                    'user_id' => $user->id,
                ],
                ['role' => $invitation->role],
            );

            return $membership->load(['user:id,name,email', 'workspace']);
        }, 5);
    }
}
