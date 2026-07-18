<?php

namespace App\Actions;

use App\Enums\TodoStatus;
use App\Models\Todo;

class CompleteTodo
{
    public function handle(Todo $todo): Todo
    {
        $todo->update([
            'status' => TodoStatus::Completed,
            'completed_at' => now(),
        ]);

        return $todo->fresh();
    }
}
