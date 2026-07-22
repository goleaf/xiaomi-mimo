<?php

namespace App\Actions;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class UpdateTodo
{
    public function __construct(
        private TransitionTodoDefinitions $transition,
        private ConfigureTodoRecurrence $configureRecurrence,
        private GenerateRecurringTodoOccurrence $generateOccurrence,
    ) {}

    /**
     * @param  array{project_id?: string|null, assigned_to?: string|null, title?: string, description?: string|null, status?: string, priority?: string, due_date?: string|null, start_date?: string|null, estimated_time?: int|null, spent_time?: int|null, is_recurring?: bool, recurring_rule?: string|null, label_ids?: list<string>|null, tag_ids?: list<string>|null}  $data
     */
    public function handle(Todo $todo, array $data): Todo
    {
        $updated = DB::transaction(function () use ($todo, $data): Todo {
            $todo->loadMissing(['workspace', 'statusDefinition', 'priorityDefinition']);
            $previousRule = $todo->recurring_rule;
            $previousDueDate = $todo->due_date?->toDateString();
            $wasRecurring = $todo->is_recurring;
            $fillable = collect($data)->only([
                'project_id', 'assigned_to', 'title', 'description',
                'due_date', 'start_date', 'estimated_time', 'spent_time',
                'is_recurring', 'recurring_rule',
            ])->toArray();

            if (array_key_exists('is_recurring', $data) && ! $data['is_recurring']) {
                $fillable['recurring_rule'] = null;
            }
            $definitionInput = collect($data)->only([
                'status', 'status_id', 'priority', 'priority_id',
            ])->toArray();

            if ($definitionInput !== []) {
                $fillable = [
                    ...$fillable,
                    ...$this->transition->attributes($todo->workspace, $definitionInput, $todo),
                ];
            }

            $todo->update($fillable);
            $restartRecurrence = $todo->is_recurring && (
                ! $wasRecurring
                || $previousRule !== $todo->recurring_rule
                || $previousDueDate !== $todo->due_date?->toDateString()
            );
            $todo = $this->configureRecurrence->handle($todo, $restartRecurrence);

            if (array_key_exists('label_ids', $data)) {
                $todo->labels()->sync($data['label_ids'] ?? []);
            }

            if (array_key_exists('tag_ids', $data)) {
                $todo->tags()->sync($data['tag_ids'] ?? []);
            }

            return $todo->refresh()->load([
                'project', 'assignee', 'labels', 'tags',
                'statusDefinition', 'priorityDefinition',
            ]);
        }, 5);

        if ($updated->completed_at !== null) {
            $this->generateOccurrence->handle($updated);
        }

        return $updated->refresh();
    }
}
