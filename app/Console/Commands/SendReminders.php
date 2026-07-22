<?php

namespace App\Console\Commands;

use App\Actions\ClaimDueReminders;
use App\Jobs\DeliverReminder;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send {--limit=100 : Maximum due reminders to claim}';

    protected $description = 'Send pending reminders that are due';

    public function handle(ClaimDueReminders $claimDueReminders): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 500],
        ]);

        if ($limit === false) {
            $this->error('The limit must be between 1 and 500.');

            return self::FAILURE;
        }

        $reminders = $claimDueReminders->handle($limit);

        foreach ($reminders as $reminder) {
            DeliverReminder::dispatch($reminder->id, (string) $reminder->claim_token);
        }

        $this->info("Claimed {$reminders->count()} reminders for delivery.");

        return self::SUCCESS;
    }
}
