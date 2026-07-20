<?php

namespace App\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateWorkspaceMemberRole
{
    public function handle(WorkspaceMember $membership, User $actor, WorkspaceRole $role): WorkspaceMember
    {
        return DB::transaction(function () use ($membership, $actor, $role): WorkspaceMember {
            $updated = WorkspaceMember::query()
                ->whereKey($membership->id)
                ->where('workspace_id', $membership->workspace_id)
                ->whereNot('role', WorkspaceRole::Owner)
                ->whereHas('workspace.memberships', fn ($query) => $query
                    ->where('user_id', $actor->id)
                    ->whereIn('role', [WorkspaceRole::Owner, WorkspaceRole::Admin]))
                ->update(['role' => $role]);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'role' => [__('validation.in', ['attribute' => 'role'])],
                ]);
            }

            return WorkspaceMember::query()
                ->with(['user:id,name,email', 'workspace'])
                ->findOrFail($membership->id);
        }, 5);
    }
}
