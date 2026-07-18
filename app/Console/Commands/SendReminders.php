<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Send pending reminders that are due';

    public function handle(): int
    {
        $reminders = Reminder::where('is_sent', false)
            ->where('reminded_at', '<=', now())
            ->with('todo', 'user')
            ->get();

        $count = 0;

        foreach ($reminders as $reminder) {
            if ($reminder->todo && $reminder->user) {
                $reminder->user->notify(new \App\Notifications\ReminderNotification($reminder));
                $reminder->update(['is_sent' => true]);
                $count++;
            }
        }

        $this->info("Sent {$count} reminders.");

        return self::SUCCESS;
    }
}
