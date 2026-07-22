<?php

namespace App\Actions;

use App\Models\Todo;

class CompleteTodo
{
    public function __construct(
        private TransitionTodoDefinitions $transition,
        private GenerateRecurringTodoOccurrence $generateOccurrence,
    ) {}

    public function handle(Todo $todo): Todo
    {
        $todo->loadMissing(['workspace', 'statusDefinition', 'priorityDefinition']);

        $todo = $this->transition->complete($todo);
        $this->generateOccurrence->handle($todo);

        return $todo->refresh();
    }
}
