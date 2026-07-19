<?php

namespace App\Actions;

use App\Models\Todo;

class BulkDeleteTodos
{
    /** @param list<string> $todoIds */
    public function handle(array $todoIds): int
    {
        return Todo::whereIn('id', $todoIds)->delete();
    }
}
