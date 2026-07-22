<?php

namespace App\Actions;

use App\Enums\ActivityEvent;
use App\Models\Todo;
use App\Services\RecurrenceSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GenerateRecurringTodoOccurrence
{
    public function __construct(
        private ConfigureTodoRecurrence $configureRecurrence,
        private RecurrenceSchedule $schedule,
        private TransitionTodoDefinitions $transition,
        private LogActivity $logActivity,
    ) {}

    public function handle(Todo $todo): ?Todo
    {
        return DB::transaction(function () use ($todo): ?Todo {
            $source = Todo::query()
                ->whereKey($todo->id)
                ->lockForUpdate()
                ->first();

            if (! $source instanceof Todo
                || ! $source->is_recurring
                || $source->recurring_rule === null
                || $source->completed_at === null) {
                return null;
            }

            $source = $this->configureRecurrence->handle($source);

            if ($source->recurrence_generated_at !== null
                || $source->recurrence_series_id === null
                || $source->recurrence_sequence === null
                || $source->recurrence_anchor_date === null) {
                return null;
            }

            $nextSequence = $source->recurrence_sequence + 1;
            $nextDate = $this->schedule->occurrence(
                $source->recurrence_anchor_date,
                $source->recurring_rule,
                $nextSequence,
            );
            $generatedAt = now();
            $claimed = Todo::query()
                ->whereKey($source->id)
                ->whereNull('recurrence_generated_at')
                ->update(['recurrence_generated_at' => $generatedAt]);

            if ($claimed !== 1) {
                return null;
            }

            $source->forceFill(['recurrence_generated_at' => $generatedAt])->syncOriginal();
            $source->load([
                'workspace.owner.preferences',
                'assignee.preferences',
                'statusDefinition',
                'priorityDefinition',
                'labels',
                'tags',
                'checklists.items',
            ]);
            $existing = Todo::withTrashed()
                ->where('recurrence_series_id', $source->recurrence_series_id)
                ->where('recurrence_sequence', $nextSequence)
                ->first();

            if ($existing instanceof Todo) {
                return $existing->trashed() ? null : $existing;
            }

            $occurrence = $source->replicate();
            $occurrence->fill($this->transition->attributes($source->workspace, [
                'priority_id' => $source->priority_id,
            ]));
            $occurrence->setAttribute('due_date', $nextDate->toDateString());
            $occurrence->setAttribute('start_date', $this->shiftStartDate($source, $nextDate));
            $occurrence->spent_time = null;
            $occurrence->is_pinned = false;
            $occurrence->is_favorite = false;
            $occurrence->is_archived = false;
            $occurrence->recurrence_series_id = $source->recurrence_series_id;
            $occurrence->recurrence_sequence = $nextSequence;
            $occurrence->recurrence_anchor_date = $source->recurrence_anchor_date;
            $occurrence->setAttribute('recurrence_occurrence_date', $nextDate->toDateString());
            $occurrence->recurrence_generated_at = null;
            $occurrence->position = ((int) $source->workspace->todos()
                ->where('project_id', $source->project_id)
                ->max('position')) + 1;
            $occurrence->save();

            $occurrence->labels()->sync($source->labels->modelKeys());
            $occurrence->tags()->sync($source->tags->modelKeys());

            foreach ($source->checklists as $checklist) {
                $copiedChecklist = $occurrence->checklists()->create([
                    'name' => $checklist->name,
                    'position' => $checklist->position,
                ]);

                foreach ($checklist->items as $item) {
                    $copiedChecklist->items()->create([
                        'content' => $item->content,
                        'is_checked' => false,
                        'position' => $item->position,
                    ]);
                }
            }

            $this->logActivity->handle(
                $occurrence,
                ActivityEvent::RecurrenceGenerated->value,
                properties: [
                    'source_todo_id' => $source->id,
                    'series_id' => $source->recurrence_series_id,
                    'sequence' => $nextSequence,
                    'occurrence_date' => $nextDate->toDateString(),
                ],
            );

            return $occurrence->load([
                'project', 'assignee', 'labels', 'tags', 'checklists.items',
                'statusDefinition', 'priorityDefinition',
            ]);
        }, 5);
    }

    private function shiftStartDate(Todo $source, CarbonImmutable $nextDate): ?string
    {
        if ($source->start_date === null || $source->recurrence_occurrence_date === null) {
            return null;
        }

        $offset = $source->recurrence_occurrence_date->diffInDays($source->start_date, false);

        return $nextDate->addDays($offset)->toDateString();
    }
}
