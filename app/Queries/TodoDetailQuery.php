<?php

namespace App\Queries;

use App\Models\Label;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

class TodoDetailQuery
{
    public function todo(Todo $todo): Todo
    {
        return $todo->load([
            'workspace', 'project', 'assignee', 'labels', 'tags', 'comments.user',
            'checklists.items', 'attachments.user', 'reminders', 'subtasks',
            'statusDefinition', 'priorityDefinition',
        ]);
    }

    /** @return Collection<int, Label> */
    public function availableLabels(Todo $todo): Collection
    {
        return Label::query()
            ->where('workspace_id', $todo->workspace_id)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, TaskStatus> */
    public function statuses(Todo $todo): Collection
    {
        return $todo->workspace->taskStatuses()->ordered()->get();
    }

    /** @return Collection<int, TaskPriority> */
    public function priorities(Todo $todo): Collection
    {
        return $todo->workspace->taskPriorities()->ordered()->get();
    }
}
