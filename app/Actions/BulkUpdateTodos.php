<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkUpdateTodos
{
    public function __construct(private TransitionTodoDefinitions $transition) {}

    /** @param list<string> $todoIds */
    public function setCompletion(Workspace $workspace, array $todoIds, bool $completed): int
    {
        return DB::transaction(function () use ($workspace, $todoIds, $completed): int {
            $todos = $workspace->todos()
                ->whereIn('id', $todoIds)
                ->with(['workspace', 'statusDefinition', 'priorityDefinition'])
                ->get();

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
        return $workspace->todos()
            ->whereIn('id', $todoIds)
            ->update(['is_archived' => $archived]);
    }
}
