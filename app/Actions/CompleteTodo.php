<?php

namespace App\Actions;

use App\Models\Todo;

class CompleteTodo
{
    public function __construct(private TransitionTodoDefinitions $transition) {}

    public function handle(Todo $todo): Todo
    {
        $todo->loadMissing(['workspace', 'statusDefinition', 'priorityDefinition']);

        return $this->transition->complete($todo);
    }
}
