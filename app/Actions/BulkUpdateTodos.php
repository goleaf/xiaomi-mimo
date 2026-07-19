<?php

namespace App\Actions;

use App\Models\Todo;

class BulkUpdateTodos
{
    /**
     * @param  list<string>  $todoIds
     * @param  array{status?: string, priority?: string, project_id?: string|null, assigned_to?: string|null, is_archived?: bool}  $data
     */
    public function handle(array $todoIds, array $data): int
    {
        return Todo::whereIn('id', $todoIds)->update(
            collect($data)->only(['status', 'priority', 'project_id', 'assigned_to', 'is_archived'])->toArray()
        );
    }
}
