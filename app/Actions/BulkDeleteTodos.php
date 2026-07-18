<?php

namespace App\Actions;

use App\Models\Todo;

class BulkDeleteTodos
{
    public function handle(array $todoIds): int
    {
        return Todo::whereIn('id', $todoIds)->delete();
    }
}
