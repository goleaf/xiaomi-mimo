<?php

namespace App\Queries;

use App\Models\Label;
use App\Models\Tag;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;

class TodoDetailQuery
{
    public function todo(Todo $todo): Todo
    {
        return $todo->load([
            'workspace',
            'workspace.labels' => fn ($query) => $query->orderBy('name'),
            'workspace.tags' => fn ($query) => $query->orderBy('name'),
            'project', 'assignee', 'labels', 'tags',
            'comments' => fn ($query) => $query
                ->with(['user', 'todo.workspace'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(20),
            'checklists.items', 'attachments.user', 'attachments.todo.workspace', 'reminders', 'subtasks',
            'statusDefinition', 'priorityDefinition',
        ])->loadCount('comments');
    }

    /** @return Collection<int, Label> */
    public function availableLabels(Todo $todo): Collection
    {
        return Label::query()
            ->where('workspace_id', $todo->workspace_id)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Tag> */
    public function availableTags(Todo $todo): Collection
    {
        return Tag::query()
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
