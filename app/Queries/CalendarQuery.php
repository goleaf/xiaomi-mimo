<?php

namespace App\Queries;

use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class CalendarQuery
{
    /** @return Collection<int, Todo> */
    public function datedTodos(Workspace $workspace): Collection
    {
        return $workspace->todos()
            ->select([
                'id', 'project_id', 'workspace_id', 'title', 'status', 'status_id',
                'priority', 'priority_id', 'due_date', 'completed_at',
            ])
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->with([
                'project:id,name,color',
                'statusDefinition:id,workspace_id,key,name,translation_key,color,is_completed',
                'priorityDefinition:id,workspace_id,key,name,translation_key,color',
            ])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }
}
