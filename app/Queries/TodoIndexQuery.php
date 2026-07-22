<?php

namespace App\Queries;

use App\Models\Project;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use App\Models\Workspace;
use App\Services\TodoFilterService;
use App\Services\TodoSortService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TodoIndexQuery
{
    public function __construct(
        private TodoFilterService $filterService,
        private TodoSortService $sortService,
    ) {}

    /**
     * @param  array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null}  $filters
     * @return LengthAwarePaginator<int, Todo>
     */
    public function todos(
        Workspace $workspace,
        array $filters,
        ?string $sort,
        ?string $direction,
        int $perPage,
    ): LengthAwarePaginator {
        $query = $this->filtered($workspace, $filters)
            ->with(['project', 'assignee', 'labels', 'tags', 'statusDefinition', 'priorityDefinition'])
            ->active();

        $query = $this->sortService->apply($query, $sort, $direction);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null}  $filters
     * @return array{total: int, pending: int, completed: int}
     */
    public function stats(Workspace $workspace, array $filters): array
    {
        $counts = (array) $this->filtered($workspace, $filters)
            ->active()
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN completed_at IS NULL THEN 1 END) AS pending')
            ->selectRaw('COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) AS completed')
            ->first();

        return [
            'total' => (int) ($counts['total'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
        ];
    }

    /** @return Collection<int, Project> */
    public function projects(Workspace $workspace): Collection
    {
        return $workspace->projects()->active()->get();
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

    /**
     * @param  array{search?: string|null, project_id?: string|null, status?: string|null, priority?: string|null, assigned_to?: string|null, label_id?: string|null, tag_id?: string|null, is_pinned?: bool|null, is_favorite?: bool|null, due_date_from?: string|null, due_date_to?: string|null, overdue?: bool|null, completed_today?: bool|null}  $filters
     * @return Builder<Todo>
     */
    private function filtered(Workspace $workspace, array $filters): Builder
    {
        return $this->filterService->apply(
            $workspace->todos()->getQuery(),
            $filters,
        );
    }
}
