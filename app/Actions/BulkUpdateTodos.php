<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkUpdateTodos
{
    public function __construct(
        private TransitionTodoDefinitions $transition,
        private ResolveWorkspaceTodos $resolveTodos,
    ) {}

    /** @param list<string> $todoIds */
    public function setCompletion(Workspace $workspace, array $todoIds, bool $completed): int
    {
        return DB::transaction(function () use ($workspace, $todoIds, $completed): int {
            $todos = $this->resolveTodos->handle($workspace, $todoIds);
            $todos->load(['workspace', 'statusDefinition', 'priorityDefinition']);

            foreach ($todos as $todo) {
                $completed
                    ? $this->transition->complete($todo)
                    : $this->transition->uncomplete($todo);
            }

            return $todos->count();
        }, 5);
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
