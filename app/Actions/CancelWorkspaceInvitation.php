<?php

namespace App\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CancelWorkspaceInvitation
{
    public function handle(WorkspaceInvitation $invitation, User $actor): void
    {
        DB::transaction(function () use ($invitation, $actor): void {
            $updated = WorkspaceInvitation::query()
                ->whereKey($invitation->id)
                ->whereNull('accepted_at')
                ->whereNull('cancelled_at')
                ->whereHas('workspace.memberships', fn ($query) => $query
                    ->where('user_id', $actor->id)
                    ->whereIn('role', [WorkspaceRole::Owner, WorkspaceRole::Admin]))
                ->update([
                    'cancelled_at' => now(),
                    'token_hash' => hash('sha256', Str::random(64)),
                ]);

            if ($updated !== 1) {
                throw new AuthorizationException;
            }
        }, 5);
    }
}
