<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceInvitation;

class WorkspaceInvitationPolicy
{
    public function view(User $user, WorkspaceInvitation $workspaceInvitation): bool
    {
        return $user->can('manageMembers', $workspaceInvitation->workspace);
    }

    public function resend(User $user, WorkspaceInvitation $workspaceInvitation): bool
    {
        return $workspaceInvitation->accepted_at === null
            && $workspaceInvitation->cancelled_at === null
            && $user->can('manageMembers', $workspaceInvitation->workspace);
    }

    public function cancel(User $user, WorkspaceInvitation $workspaceInvitation): bool
    {
        return $this->resend($user, $workspaceInvitation);
    }
}
