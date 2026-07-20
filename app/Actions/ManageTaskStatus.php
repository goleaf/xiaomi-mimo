<?php

namespace App\Actions;

use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageTaskStatus
{
    /** @param array{name: string, color: string, is_completed?: bool} $data */
    public function create(Workspace $workspace, array $data): TaskStatus
    {
        return DB::transaction(function () use ($workspace, $data): TaskStatus {
            $key = $this->uniqueKey($workspace, $data['name']);

            return $workspace->taskStatuses()->create([
                ...$data,
                'key' => $key,
                'translation_key' => null,
                'position' => ((int) $workspace->taskStatuses()->max('position')) + 1,
                'is_default' => false,
                'is_completion_target' => false,
                'is_archived' => false,
            ])->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    /** @param array{name: string, color: string, is_completed?: bool} $data */
    public function update(TaskStatus $status, array $data): TaskStatus
    {
        return DB::transaction(function () use ($status, $data): TaskStatus {
            if ($status->is_completion_target && array_key_exists('is_completed', $data) && ! $data['is_completed']) {
                throw ValidationException::withMessages([
                    'is_completed' => __('ui.workspaces.management.configuration.statuses.completion_target_must_be_completed'),
                ]);
            }

            if ($status->is_default && ($data['is_completed'] ?? false)) {
                throw ValidationException::withMessages([
                    'is_completed' => __('ui.workspaces.management.configuration.statuses.default_must_be_active_open'),
                ]);
            }

            $wasCompleted = $status->is_completed;
            $status->update([...$data, 'translation_key' => null]);

            if (array_key_exists('is_completed', $data) && $wasCompleted !== $status->is_completed) {
                $status->todos()->withTrashed()->update([
                    'completed_at' => $status->is_completed ? now() : null,
                ]);
            }

            return $status->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    public function setDefault(TaskStatus $status): TaskStatus
    {
        if ($status->is_archived || $status->is_completed) {
            throw ValidationException::withMessages([
                'status' => __('ui.workspaces.management.configuration.statuses.default_must_be_active_open'),
            ]);
        }

        return DB::transaction(function () use ($status): TaskStatus {
            TaskStatus::query()
                ->where('workspace_id', $status->workspace_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            $status->update(['is_default' => true]);

            return $status->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    public function setCompletionTarget(TaskStatus $status): TaskStatus
    {
        if ($status->is_archived || ! $status->is_completed) {
            throw ValidationException::withMessages([
                'status' => __('ui.workspaces.management.configuration.statuses.target_must_be_active_completed'),
            ]);
        }

        return DB::transaction(function () use ($status): TaskStatus {
            TaskStatus::query()
                ->where('workspace_id', $status->workspace_id)
                ->where('is_completion_target', true)
                ->update(['is_completion_target' => false]);
            $status->update(['is_completion_target' => true]);

            return $status->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    public function archive(TaskStatus $status): TaskStatus
    {
        if ($status->is_default || $status->is_completion_target) {
            throw ValidationException::withMessages([
                'status' => __('ui.workspaces.management.configuration.statuses.protected_status'),
            ]);
        }

        $status->update(['is_archived' => true]);

        return $status->load('workspace')->loadCount(['allTodos as todos_count']);
    }

    public function restore(TaskStatus $status): TaskStatus
    {
        $status->update(['is_archived' => false]);

        return $status->load('workspace')->loadCount(['allTodos as todos_count']);
    }

    /** @param list<string> $ids */
    public function reorder(Workspace $workspace, array $ids): void
    {
        $expectedIds = $workspace->taskStatuses()->orderBy('position')->pluck('id')->all();

        if (count($ids) !== count(array_unique($ids))
            || count($ids) !== count($expectedIds)
            || array_diff($ids, $expectedIds) !== []
            || array_diff($expectedIds, $ids) !== []) {
            throw ValidationException::withMessages([
                'ids' => __('ui.workspaces.management.configuration.invalid_order'),
            ]);
        }

        DB::transaction(function () use ($workspace, $ids): void {
            foreach ($ids as $position => $id) {
                $workspace->taskStatuses()->whereKey($id)->update(['position' => $position]);
            }
        }, 5);
    }

    public function delete(TaskStatus $status, ?TaskStatus $replacement): void
    {
        DB::transaction(function () use ($status, $replacement): void {
            $usageCount = $status->todos()->withTrashed()->count();
            $needsReplacement = $usageCount > 0 || $status->is_default || $status->is_completion_target;

            if ($needsReplacement && ! $replacement instanceof TaskStatus) {
                throw ValidationException::withMessages([
                    'replacement_id' => __('ui.workspaces.management.configuration.replacement_required'),
                ]);
            }

            if ($replacement instanceof TaskStatus) {
                if ($replacement->workspace_id !== $status->workspace_id
                    || $replacement->is($status)
                    || $replacement->is_archived) {
                    throw ValidationException::withMessages([
                        'replacement_id' => __('ui.workspaces.management.configuration.invalid_replacement'),
                    ]);
                }

                $status->todos()->withTrashed()->update([
                    'status_id' => $replacement->id,
                    'status' => $replacement->key,
                    'completed_at' => $replacement->is_completed
                        ? ($status->is_completed ? DB::raw('completed_at') : now())
                        : null,
                ]);

                if ($status->is_default && ! $replacement->is_completed) {
                    TaskStatus::query()
                        ->where('workspace_id', $status->workspace_id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                    $replacement->update(['is_default' => true]);
                }

                if ($status->is_completion_target && $replacement->is_completed) {
                    TaskStatus::query()
                        ->where('workspace_id', $status->workspace_id)
                        ->where('is_completion_target', true)
                        ->update(['is_completion_target' => false]);
                    $replacement->update(['is_completion_target' => true]);
                }
            }

            if ($status->is_default && ($replacement === null || $replacement->is_completed)) {
                throw ValidationException::withMessages([
                    'replacement_id' => __('ui.workspaces.management.configuration.statuses.default_must_be_active_open'),
                ]);
            }

            if ($status->is_completion_target && ($replacement === null || ! $replacement->is_completed)) {
                throw ValidationException::withMessages([
                    'replacement_id' => __('ui.workspaces.management.configuration.statuses.target_must_be_active_completed'),
                ]);
            }

            $status->delete();
        }, 5);
    }

    private function uniqueKey(Workspace $workspace, string $name): string
    {
        $base = Str::snake(Str::slug($name, '_'));
        $base = $base !== '' ? Str::limit($base, 48, '') : 'status';
        $key = $base;
        $suffix = 2;

        while ($workspace->taskStatuses()->where('key', $key)->exists()) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }
}
