<?php

namespace App\Console\Commands;

use App\Actions\GenerateRecurringTodoOccurrence;
use App\Models\Todo;
use Illuminate\Console\Command;

class ProcessRecurringTasks extends Command
{
    protected $signature = 'tasks:recurring {--limit=100 : Maximum completed occurrences to inspect}';

    protected $description = 'Create new task instances for completed recurring tasks';

    public function handle(GenerateRecurringTodoOccurrence $generate): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 500],
        ]);

        if ($limit === false) {
            $this->error('The limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $completedRecurring = Todo::query()
            ->where('is_recurring', true)
            ->whereNotNull('recurring_rule')
            ->whereNotNull('completed_at')
            ->whereNull('recurrence_generated_at')
            ->orderBy('completed_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $created = 0;

        foreach ($completedRecurring as $todo) {
            if ($generate->handle($todo) instanceof Todo) {
                $created++;
            }
        }

        $this->info("Processed {$created} recurring tasks.");

        return self::SUCCESS;
    }
}
