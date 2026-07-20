<?php

namespace App\Actions;

use App\Models\Workspace;

class BulkDeleteTodos
{
    /** @param list<string> $todoIds */
    public function handle(Workspace $workspace, array $todoIds): int
    {
        return $workspace->todos()->whereIn('id', $todoIds)->delete();
    }
}
