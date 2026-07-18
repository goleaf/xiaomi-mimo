<?php

namespace App\Actions;

use App\Enums\TodoStatus;
use App\Models\Todo;

class UncompleteTodo
{
    public function handle(Todo $todo): Todo
    {
        $todo->update([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        return $todo->fresh();
    }
}
