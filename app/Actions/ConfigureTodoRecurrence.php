<?php

namespace App\Actions;

use App\Models\Todo;
use App\Services\RecurrenceSchedule;

class ConfigureTodoRecurrence
{
    public function __construct(private RecurrenceSchedule $schedule) {}

    public function handle(Todo $todo, bool $restart = false): Todo
    {
        if (! $todo->is_recurring || $todo->recurring_rule === null) {
            if ($todo->recurrence_series_id === null
                && $todo->recurrence_sequence === null
                && $todo->recurrence_anchor_date === null
                && $todo->recurrence_occurrence_date === null
                && $todo->recurrence_generated_at === null) {
                return $todo;
            }

            $todo->update([
                'recurrence_series_id' => null,
                'recurrence_sequence' => null,
                'recurrence_anchor_date' => null,
                'recurrence_occurrence_date' => null,
                'recurrence_generated_at' => null,
            ]);

            return $todo->refresh();
        }

        if (! $restart && $todo->recurrence_series_id !== null) {
            return $todo;
        }

        $canonicalRule = $this->schedule->canonicalRule($todo->recurring_rule);
        $anchor = $this->schedule->anchorFor($todo);
        $todo->update([
            'recurring_rule' => $canonicalRule,
            'recurrence_series_id' => $todo->id,
            'recurrence_sequence' => 0,
            'recurrence_anchor_date' => $anchor->toDateString(),
            'recurrence_occurrence_date' => $anchor->toDateString(),
            'recurrence_generated_at' => null,
        ]);

        return $todo->refresh();
    }
}
