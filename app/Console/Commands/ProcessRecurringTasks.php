<?php

namespace App\Console\Commands;

use App\Actions\TransitionTodoDefinitions;
use App\Models\Todo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessRecurringTasks extends Command
{
    protected $signature = 'tasks:recurring';

    protected $description = 'Create new task instances for completed recurring tasks';

    public function handle(TransitionTodoDefinitions $transition): int
    {
        $completedRecurring = Todo::where('is_recurring', true)
            ->where('status', 'completed')
            ->whereNotNull('recurring_rule')
            ->whereNotNull('completed_at')
            ->with(['workspace', 'statusDefinition', 'priorityDefinition', 'labels', 'tags'])
            ->get();

        $created = 0;

        foreach ($completedRecurring as $todo) {
            $nextDueDate = $this->calculateNextDueDate($todo->completed_at, $todo->recurring_rule);

            if ($nextDueDate && $nextDueDate->isFuture()) {
                $newTodo = $todo->replicate();
                $newTodo->fill($transition->attributes($todo->workspace, [
                    'priority_id' => $todo->priority_id,
                ]));
                $newTodo->due_date = $nextDueDate;
                $newTodo->is_pinned = false;
                $newTodo->is_favorite = false;
                $newTodo->position = ((int) $todo->workspace->todos()->max('position')) + 1;
                $newTodo->save();

                // Copy labels and tags
                $newTodo->labels()->sync($todo->labels->pluck('id'));
                $newTodo->tags()->sync($todo->tags->pluck('id'));

                // Reset the original task
                $transition->uncomplete($todo);

                $created++;
            }
        }

        $this->info("Processed {$created} recurring tasks.");

        return self::SUCCESS;
    }

    private function calculateNextDueDate(Carbon $completedAt, string $rule): ?Carbon
    {
        $parts = explode(';', $rule);
        $freq = null;
        $interval = 1;

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part);
            if ($key === 'FREQ') {
                $freq = $value;
            }
            if ($key === 'INTERVAL') {
                $interval = (int) $value;
            }
        }

        if (! $freq) {
            return null;
        }

        $base = $completedAt->copy();

        return match ($freq) {
            'DAILY' => $base->addDays($interval),
            'WEEKLY' => $base->addWeeks($interval),
            'MONTHLY' => $base->addMonths($interval),
            'YEARLY' => $base->addYears($interval),
            default => null,
        };
    }
}
