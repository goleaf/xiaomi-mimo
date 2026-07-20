<?php

namespace App\Services;

use App\Enums\TodoStatus;
use App\Models\Todo;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class DashboardQuery
{
    /**
     * @return array{
     *     stats: array{today_count: int, overdue_count: int, completed_today: int, total_tasks: int, completed_total: int, completion_rate: int},
     *     todayTasks: EloquentCollection<int, Todo>,
     *     overdueTasks: EloquentCollection<int, Todo>,
     *     upcomingTasks: EloquentCollection<int, Todo>,
     *     weeklyData: Collection<int, array{date: string, completed: int, created: int}>
     * }
     */
    public function forWorkspace(Workspace $workspace, string $timezone): array
    {
        $localToday = CarbonImmutable::now($timezone)->startOfDay();
        $today = $localToday->toDateString();
        $upcomingEnd = $localToday->addDays(7)->toDateString();
        $todayStartUtc = $localToday->utc()->toDateTimeString();
        $tomorrowStartUtc = $localToday->addDay()->utc()->toDateTimeString();
        $todos = Todo::query()->forWorkspace($workspace->id)->active();

        $todayTasks = (clone $todos)
            ->whereDate('due_date', $today)
            ->with('project')
            ->orderBy('position')
            ->get();
        $overdueTasks = (clone $todos)
            ->whereDate('due_date', '<', $today)
            ->where('status', '!=', TodoStatus::Completed)
            ->with('project')
            ->orderBy('due_date')
            ->limit(10)
            ->get();
        $upcomingTasks = (clone $todos)
            ->whereDate('due_date', '>', $today)
            ->whereDate('due_date', '<=', $upcomingEnd)
            ->where('status', '!=', TodoStatus::Completed)
            ->with('project')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $stats = $this->stats($todos, $today, $todayStartUtc, $tomorrowStartUtc);

        return [
            'stats' => $stats,
            'todayTasks' => $todayTasks,
            'overdueTasks' => $overdueTasks,
            'upcomingTasks' => $upcomingTasks,
            'weeklyData' => $this->weeklyData($todos, $localToday),
        ];
    }

    /**
     * @return array{
     *     stats: array{today_count: int, overdue_count: int, completed_today: int, total_tasks: int, completed_total: int, completion_rate: int},
     *     todayTasks: list<never>,
     *     overdueTasks: list<never>,
     *     upcomingTasks: list<never>,
     *     weeklyData: list<never>
     * }
     */
    public function empty(): array
    {
        return [
            'stats' => [
                'today_count' => 0,
                'overdue_count' => 0,
                'completed_today' => 0,
                'total_tasks' => 0,
                'completed_total' => 0,
                'completion_rate' => 0,
            ],
            'todayTasks' => [],
            'overdueTasks' => [],
            'upcomingTasks' => [],
            'weeklyData' => [],
        ];
    }

    /**
     * @param  Builder<Todo>  $todos
     * @return array{today_count: int, overdue_count: int, completed_today: int, total_tasks: int, completed_total: int, completion_rate: int}
     */
    private function stats(
        Builder $todos,
        string $today,
        string $todayStartUtc,
        string $tomorrowStartUtc,
    ): array {
        $counts = (array) (clone $todos)
            ->toBase()
            ->selectRaw('COUNT(*) AS total_tasks')
            ->selectRaw('COUNT(CASE WHEN DATE(due_date) = ? THEN 1 END) AS today_count', [$today])
            ->selectRaw(
                'COUNT(CASE WHEN DATE(due_date) < ? AND status != ? THEN 1 END) AS overdue_count',
                [$today, TodoStatus::Completed->value],
            )
            ->selectRaw(
                'COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_today',
                [$todayStartUtc, $tomorrowStartUtc],
            )
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS completed_total', [TodoStatus::Completed->value])
            ->first();

        $totalTasks = (int) ($counts['total_tasks'] ?? 0);
        $completedTotal = (int) ($counts['completed_total'] ?? 0);

        return [
            'today_count' => (int) ($counts['today_count'] ?? 0),
            'overdue_count' => (int) ($counts['overdue_count'] ?? 0),
            'completed_today' => (int) ($counts['completed_today'] ?? 0),
            'total_tasks' => $totalTasks,
            'completed_total' => $completedTotal,
            'completion_rate' => $totalTasks > 0
                ? (int) round(($completedTotal / $totalTasks) * 100)
                : 0,
        ];
    }

    /**
     * @param  Builder<Todo>  $todos
     * @return Collection<int, array{date: string, completed: int, created: int}>
     */
    private function weeklyData(Builder $todos, CarbonImmutable $localToday): Collection
    {
        /** @var Collection<int, CarbonImmutable> $days */
        $days = collect(range(6, 0))->map(
            fn (int $daysAgo): CarbonImmutable => $localToday->subDays($daysAgo),
        );
        $bindings = [];

        foreach ($days as $day) {
            $startUtc = $day->utc()->toDateTimeString();
            $endUtc = $day->addDay()->utc()->toDateTimeString();

            array_push($bindings, $startUtc, $endUtc, $startUtc, $endUtc);
        }

        $weeklyCounts = (array) (clone $todos)
            ->toBase()
            ->selectRaw(
                <<<'SQL'
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_0,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_0,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_1,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_1,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_2,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_2,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_3,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_3,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_4,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_4,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_5,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_5,
                COUNT(CASE WHEN completed_at >= ? AND completed_at < ? THEN 1 END) AS completed_6,
                COUNT(CASE WHEN created_at >= ? AND created_at < ? THEN 1 END) AS created_6
                SQL,
                $bindings,
            )
            ->first();

        return $days->map(fn (CarbonImmutable $day, int $index): array => [
            'date' => $day->toDateString(),
            'completed' => (int) ($weeklyCounts["completed_{$index}"] ?? 0),
            'created' => (int) ($weeklyCounts["created_{$index}"] ?? 0),
        ]);
    }
}
