<?php

namespace App\Actions;

use App\Models\Workspace;

class DeleteWorkspace
{
    public function handle(Workspace $workspace): bool
    {
        return $workspace->delete();
    }
}
