<?php

namespace App\Actions;

use App\Models\Todo;

class ArchiveTodo
{
    public function handle(Todo $todo): Todo
    {
        $todo->update(['is_archived' => true]);

        return $todo->fresh();
    }
}
