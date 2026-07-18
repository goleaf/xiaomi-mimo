<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwner($user) || $workspace->memberRole($user) === 'admin';
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwner($user);
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwner($user) || $workspace->memberRole($user) === 'admin';
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->isOwner($user) || $workspace->memberRole($user) === 'admin';
    }
}
