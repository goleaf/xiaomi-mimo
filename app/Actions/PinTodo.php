<?php

namespace App\Actions;

use App\Models\Todo;

class PinTodo
{
    public function handle(Todo $todo): Todo
    {
        $todo->update(['is_pinned' => ! $todo->is_pinned]);

        return $todo->fresh();
    }
}
