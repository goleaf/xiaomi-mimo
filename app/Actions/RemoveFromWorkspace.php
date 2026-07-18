<?php

namespace App\Actions;

use App\Models\Workspace;
use App\Models\WorkspaceMember;

class RemoveFromWorkspace
{
    public function handle(Workspace $workspace, string $userId): bool
    {
        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }
}
