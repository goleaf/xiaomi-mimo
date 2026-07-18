<?php

namespace App\Actions;

use App\Models\Todo;

class BulkUpdateTodos
{
    public function handle(array $todoIds, array $data): int
    {
        return Todo::whereIn('id', $todoIds)->update(
            collect($data)->only(['status', 'priority', 'project_id', 'assigned_to', 'is_archived'])->toArray()
        );
    }
}
