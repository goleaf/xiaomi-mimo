<?php

namespace App\Actions;

use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\Todo;
use App\Models\Workspace;
use BackedEnum;
use Illuminate\Validation\ValidationException;

class TransitionTodoDefinitions
{
    /**
     * @param  array{status?: string|BackedEnum, status_id?: string, priority?: string|BackedEnum, priority_id?: string}  $data
     * @return array{status: string, status_id: string, priority: string, priority_id: string, completed_at: mixed}
     */
    public function attributes(Workspace $workspace, array $data, ?Todo $todo = null): array
    {
        $status = $this->resolveStatus(
            $workspace,
            $data['status_id'] ?? $this->stringValue($data['status'] ?? null),
            $todo,
        );
        $priority = $this->resolvePriority(
            $workspace,
            $data['priority_id'] ?? $this->stringValue($data['priority'] ?? null),
            $todo,
        );
        $currentStatus = $todo?->relationLoaded('statusDefinition') === true
            ? $todo->getRelation('statusDefinition')
            : null;
        $wasCompleted = $currentStatus instanceof TaskStatus
            ? $currentStatus->is_completed
            : $todo?->completed_at !== null;

        return [
            'status' => $status->key,
            'status_id' => $status->id,
            'priority' => $priority->key,
            'priority_id' => $priority->id,
            'completed_at' => $status->is_completed
                ? ($wasCompleted ? $todo->completed_at : now())
                : null,
        ];
    }

    public function complete(Todo $todo): Todo
    {
        $status = $todo->workspace->taskStatuses()
            ->active()
            ->where('is_completion_target', true)
            ->first();

        if (! $status instanceof TaskStatus) {
            throw ValidationException::withMessages([
                'status' => __('ui.workspaces.management.configuration.statuses.missing_completion_target'),
            ]);
        }

        return $this->transition($todo, status: $status);
    }

    public function uncomplete(Todo $todo): Todo
    {
        $status = $todo->workspace->taskStatuses()
            ->active()
            ->where('is_default', true)
            ->where('is_completed', false)
            ->first();

        if (! $status instanceof TaskStatus) {
            throw ValidationException::withMessages([
                'status' => __('ui.workspaces.management.configuration.statuses.missing_default'),
            ]);
        }

        return $this->transition($todo, status: $status);
    }

    public function transition(
        Todo $todo,
        ?TaskStatus $status = null,
        ?TaskPriority $priority = null,
    ): Todo {
        $data = [];

        if ($status instanceof TaskStatus) {
            $data['status_id'] = $status->id;
        }

        if ($priority instanceof TaskPriority) {
            $data['priority_id'] = $priority->id;
        }

        $todo->loadMissing(['workspace', 'statusDefinition', 'priorityDefinition']);
        $todo->update($this->attributes($todo->workspace, $data, $todo));

        return $todo->refresh()->load(['statusDefinition', 'priorityDefinition']);
    }

    private function resolveStatus(Workspace $workspace, ?string $input, ?Todo $todo): TaskStatus
    {
        if ($input === null || $input === '') {
            if ($todo?->statusDefinition instanceof TaskStatus) {
                return $todo->statusDefinition;
            }

            $status = $workspace->taskStatuses()
                ->active()
                ->where('is_default', true)
                ->where('is_completed', false)
                ->first();
        } else {
            $status = $workspace->taskStatuses()
                ->where(fn ($query) => $query->where('id', $input)->orWhere('key', $input))
                ->where(function ($query) use ($todo): void {
                    $query->where('is_archived', false);

                    if ($todo?->status_id !== null) {
                        $query->orWhere('id', $todo->status_id);
                    }
                })
                ->first();
        }

        if (! $status instanceof TaskStatus) {
            throw ValidationException::withMessages([
                'status' => __('validation.exists', ['attribute' => 'status']),
            ]);
        }

        return $status;
    }

    private function resolvePriority(Workspace $workspace, ?string $input, ?Todo $todo): TaskPriority
    {
        if ($input === null || $input === '') {
            if ($todo?->priorityDefinition instanceof TaskPriority) {
                return $todo->priorityDefinition;
            }

            $priority = $workspace->taskPriorities()
                ->active()
                ->where('is_default', true)
                ->first();
        } else {
            $priority = $workspace->taskPriorities()
                ->where(fn ($query) => $query->where('id', $input)->orWhere('key', $input))
                ->where(function ($query) use ($todo): void {
                    $query->where('is_archived', false);

                    if ($todo?->priority_id !== null) {
                        $query->orWhere('id', $todo->priority_id);
                    }
                })
                ->first();
        }

        if (! $priority instanceof TaskPriority) {
            throw ValidationException::withMessages([
                'priority' => __('validation.exists', ['attribute' => 'priority']),
            ]);
        }

        return $priority;
    }

    private function stringValue(string|BackedEnum|null $value): ?string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $value;
    }
}
