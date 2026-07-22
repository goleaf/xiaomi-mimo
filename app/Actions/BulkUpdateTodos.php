<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkUpdateTodos
{
    public function __construct(
        private TransitionTodoDefinitions $transition,
        private ResolveWorkspaceTodos $resolveTodos,
        private GenerateRecurringTodoOccurrence $generateOccurrence,
    ) {}

    /** @param list<string> $todoIds */
    public function setCompletion(Workspace $workspace, array $todoIds, bool $completed): int
    {
        $recurringTodos = collect();
        $count = DB::transaction(function () use ($workspace, $todoIds, $completed, &$recurringTodos): int {
            $todos = $this->resolveTodos->handle($workspace, $todoIds);
            $todos->load(['workspace', 'statusDefinition', 'priorityDefinition']);

            foreach ($todos as $todo) {
                $completed
                    ? $this->transition->complete($todo)
                    : $this->transition->uncomplete($todo);
            }

            if ($completed) {
                $recurringTodos = $todos->filter(fn ($todo): bool => $todo->is_recurring);
            }

            return $todos->count();
        }, 5);

        foreach ($recurringTodos as $todo) {
            $this->generateOccurrence->handle($todo);
        }

        return $count;
    }

    /** @param list<string> $todoIds */
    public function setArchived(Workspace $workspace, array $todoIds, bool $archived): int
    {
        return DB::transaction(function () use ($workspace, $todoIds, $archived): int {
            $todos = $this->resolveTodos->handle($workspace, $todoIds);

            return $todos->toQuery()->update(['is_archived' => $archived]);
        }, 5);
    }
}
