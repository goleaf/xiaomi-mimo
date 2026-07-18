<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $workspace->hasMember($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->workspace->isOwner($user) || $project->workspace->memberRole($user) === 'admin';
    }

    public function archive(User $user, Project $project): bool
    {
        return $project->workspace->hasMember($user);
    }
}
