<?php

namespace App\Actions;

use App\Models\Todo;

class FavoriteTodo
{
    public function handle(Todo $todo): Todo
    {
        $todo->update(['is_favorite' => ! $todo->is_favorite]);

        return $todo->fresh();
    }
}
