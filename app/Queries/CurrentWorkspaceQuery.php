<?php

namespace App\Queries;

use App\Models\User;
use App\Models\Workspace;

class CurrentWorkspaceQuery
{
    public function forUser(User $user, ?string $selectedWorkspaceId = null): ?Workspace
    {
        if (is_string($selectedWorkspaceId) && $selectedWorkspaceId !== '') {
            $selectedWorkspace = $user->workspaces()->whereKey($selectedWorkspaceId)->first();

            if ($selectedWorkspace instanceof Workspace) {
                return $selectedWorkspace;
            }
        }

        return $user->workspaces()->first();
    }
}
