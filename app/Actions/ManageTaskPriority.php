<?php

namespace App\Actions;

use App\Models\TaskPriority;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageTaskPriority
{
    /** @param array{name: string, color: string} $data */
    public function create(Workspace $workspace, array $data): TaskPriority
    {
        return DB::transaction(function () use ($workspace, $data): TaskPriority {
            $key = $this->uniqueKey($workspace, $data['name']);

            return $workspace->taskPriorities()->create([
                ...$data,
                'key' => $key,
                'translation_key' => null,
                'position' => ((int) $workspace->taskPriorities()->max('position')) + 1,
                'is_default' => false,
                'is_archived' => false,
            ])->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    /** @param array{name: string, color: string} $data */
    public function update(TaskPriority $priority, array $data): TaskPriority
    {
        $priority->update([...$data, 'translation_key' => null]);

        return $priority->load('workspace')->loadCount(['allTodos as todos_count']);
    }

    public function setDefault(TaskPriority $priority): TaskPriority
    {
        if ($priority->is_archived) {
            throw ValidationException::withMessages([
                'priority' => __('ui.workspaces.management.configuration.priorities.default_must_be_active'),
            ]);
        }

        return DB::transaction(function () use ($priority): TaskPriority {
            TaskPriority::query()
                ->where('workspace_id', $priority->workspace_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            $priority->update(['is_default' => true]);

            return $priority->load('workspace')->loadCount(['allTodos as todos_count']);
        }, 5);
    }

    public function archive(TaskPriority $priority): TaskPriority
    {
        if ($priority->is_default) {
            throw ValidationException::withMessages([
                'priority' => __('ui.workspaces.management.configuration.priorities.protected_priority'),
            ]);
        }

        $priority->update(['is_archived' => true]);

        return $priority->load('workspace')->loadCount(['allTodos as todos_count']);
    }

    public function restore(TaskPriority $priority): TaskPriority
    {
        $priority->update(['is_archived' => false]);

        return $priority->load('workspace')->loadCount(['allTodos as todos_count']);
    }

    /** @param list<string> $ids */
    public function reorder(Workspace $workspace, array $ids): void
    {
        $expectedIds = $workspace->taskPriorities()->orderBy('position')->pluck('id')->all();

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
                $workspace->taskPriorities()->whereKey($id)->update(['position' => $position]);
            }
        }, 5);
    }

    public function delete(TaskPriority $priority, ?TaskPriority $replacement): void
    {
        DB::transaction(function () use ($priority, $replacement): void {
            $usageCount = $priority->todos()->withTrashed()->count();
            $needsReplacement = $usageCount > 0 || $priority->is_default;

            if ($needsReplacement && ! $replacement instanceof TaskPriority) {
                throw ValidationException::withMessages([
                    'replacement_id' => __('ui.workspaces.management.configuration.replacement_required'),
                ]);
            }

            if ($replacement instanceof TaskPriority) {
                if ($replacement->workspace_id !== $priority->workspace_id
                    || $replacement->is($priority)
                    || $replacement->is_archived) {
                    throw ValidationException::withMessages([
                        'replacement_id' => __('ui.workspaces.management.configuration.invalid_replacement'),
                    ]);
                }

                $priority->todos()->withTrashed()->update([
                    'priority_id' => $replacement->id,
                    'priority' => $replacement->key,
                ]);

                if ($priority->is_default) {
                    TaskPriority::query()
                        ->where('workspace_id', $priority->workspace_id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
                    $replacement->update(['is_default' => true]);
                }
            }

            $priority->delete();
        }, 5);
    }

    private function uniqueKey(Workspace $workspace, string $name): string
    {
        $base = Str::snake(Str::slug($name, '_'));
        $base = $base !== '' ? Str::limit($base, 48, '') : 'priority';
        $key = $base;
        $suffix = 2;

        while ($workspace->taskPriorities()->where('key', $key)->exists()) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }
}
