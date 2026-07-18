<?php

namespace App\Actions;

use App\Models\Todo;

class DeleteTodo
{
    public function handle(Todo $todo): bool
    {
        return $todo->delete();
    }
}
