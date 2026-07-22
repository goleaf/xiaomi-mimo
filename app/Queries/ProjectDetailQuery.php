<?php

namespace App\Queries;

use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ProjectDetailQuery
{
    /** @return Collection<int, Todo> */
    public function todos(Workspace $workspace, string $projectId): Collection
    {
        return $workspace->todos()
            ->where('project_id', $projectId)
            ->with(['assignee', 'labels', 'tags', 'statusDefinition', 'priorityDefinition'])
            ->active()
            ->orderBy('position')
            ->get();
    }

    /** @return Collection<int, TaskStatus> */
    public function statuses(Workspace $workspace): Collection
    {
        return $workspace->taskStatuses()->ordered()->get();
    }

    /** @return Collection<int, TaskPriority> */
    public function priorities(Workspace $workspace): Collection
    {
        return $workspace->taskPriorities()->ordered()->get();
    }
}
