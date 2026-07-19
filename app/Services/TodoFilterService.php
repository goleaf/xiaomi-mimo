<?php

namespace App\Services;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Builder;

class TodoFilterService
{
    /**
     * @param  Builder<Todo>  $query
     * @param  array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_archived?: bool|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, created_from?: string|null, created_to?: string|null, overdue?: bool|null, completed_today?: bool|null}  $filters
     * @return Builder<Todo>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['label_id'])) {
            $query->whereHas('labels', fn ($q) => $q->where('labels.id', $filters['label_id']));
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag_id']));
        }

        if (isset($filters['is_archived'])) {
            $query->where('is_archived', $filters['is_archived']);
        }

        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', $filters['is_pinned']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', $filters['is_favorite']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['overdue'])) {
            $query->overdue();
        }

        if (! empty($filters['completed_today'])) {
            $query->completedToday();
        }

        return $query;
    }
}
