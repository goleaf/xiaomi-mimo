<?php

namespace App\Actions;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class EnsureWorkspaceTaskDefinitions
{
    /** @var list<array<string, mixed>> */
    private const array STATUSES = [
        ['key' => 'pending', 'name' => 'To do', 'translation_key' => 'tasks.statuses.pending', 'color' => '#64748b', 'is_default' => true, 'is_completed' => false, 'is_completion_target' => false],
        ['key' => 'in_progress', 'name' => 'In progress', 'translation_key' => 'tasks.statuses.in_progress', 'color' => '#f59e0b', 'is_default' => false, 'is_completed' => false, 'is_completion_target' => false],
        ['key' => 'completed', 'name' => 'Completed', 'translation_key' => 'tasks.statuses.completed', 'color' => '#22c55e', 'is_default' => false, 'is_completed' => true, 'is_completion_target' => true],
    ];

    /** @var list<array<string, mixed>> */
    private const array PRIORITIES = [
        ['key' => 'none', 'name' => 'None', 'translation_key' => 'tasks.priorities.none', 'color' => '#94a3b8', 'is_default' => true],
        ['key' => 'low', 'name' => 'Low', 'translation_key' => 'tasks.priorities.low', 'color' => '#3b82f6', 'is_default' => false],
        ['key' => 'medium', 'name' => 'Medium', 'translation_key' => 'tasks.priorities.medium', 'color' => '#eab308', 'is_default' => false],
        ['key' => 'high', 'name' => 'High', 'translation_key' => 'tasks.priorities.high', 'color' => '#f97316', 'is_default' => false],
        ['key' => 'urgent', 'name' => 'Urgent', 'translation_key' => 'tasks.priorities.urgent', 'color' => '#ef4444', 'is_default' => false],
    ];

    public function handle(Workspace $workspace): void
    {
        DB::transaction(function () use ($workspace): void {
            if (! $workspace->taskStatuses()->exists()) {
                foreach (self::STATUSES as $position => $status) {
                    $workspace->taskStatuses()->create([
                        ...$status,
                        'position' => $position,
                        'is_archived' => false,
                    ]);
                }
            }

            if (! $workspace->taskPriorities()->exists()) {
                foreach (self::PRIORITIES as $position => $priority) {
                    $workspace->taskPriorities()->create([
                        ...$priority,
                        'position' => $position,
                        'is_archived' => false,
                    ]);
                }
            }
        }, 5);
    }
}
