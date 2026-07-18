<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class TodoSortService
{
    public function apply(Builder $query, ?string $sort = null, ?string $direction = 'asc'): Builder
    {
        return match ($sort) {
            'due_date' => $query->orderBy('due_date', $direction)->orderBy('position'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END"),
            'title' => $query->orderBy('title', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'status' => $query->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'completed' THEN 2 END"),
            default => $query->orderBy('is_pinned', 'desc')->orderBy('position'),
        };
    }
}
