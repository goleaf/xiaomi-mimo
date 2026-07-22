<?php

namespace App\Services;

use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Builder;

class TodoSortService
{
    /**
     * @param  Builder<Todo>  $query
     * @return Builder<Todo>
     */
    public function apply(Builder $query, ?string $sort = null, ?string $direction = 'asc'): Builder
    {
        $resolvedDirection = $direction === 'desc' ? 'desc' : 'asc';

        $query = match ($sort) {
            'due_date' => $query->orderBy('due_date', $resolvedDirection)->orderBy('position'),
            'priority' => $query->orderBy(
                TaskPriority::query()
                    ->select('position')
                    ->whereColumn('task_priorities.id', 'todos.priority_id'),
                $resolvedDirection,
            ),
            'title' => $query->orderBy('title', $resolvedDirection),
            'created_at' => $query->orderBy('created_at', $resolvedDirection),
            'status' => $query->orderBy(
                TaskStatus::query()
                    ->select('position')
                    ->whereColumn('task_statuses.id', 'todos.status_id'),
                $resolvedDirection,
            ),
            default => $query->orderBy('is_pinned', 'desc')->orderBy('position'),
        };

        return $query->orderBy('id');
    }
}
