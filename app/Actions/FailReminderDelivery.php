<?php

namespace App\Actions;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Support\Str;
use Throwable;

class FailReminderDelivery
{
    public function handle(string $reminderId, string $claimToken, Throwable $exception): bool
    {
        $reminder = Reminder::query()
            ->whereKey($reminderId)
            ->where('status', ReminderStatus::Processing)
            ->where('claim_token', $claimToken)
            ->first();

        if (! $reminder instanceof Reminder) {
            return false;
        }

        $nextAttemptAt = match ($reminder->attempts) {
            1 => now()->addMinute(),
            2 => now()->addMinutes(5),
            default => null,
        };

        return $reminder->update([
            'status' => ReminderStatus::Failed,
            'claim_token' => null,
            'claimed_at' => null,
            'failed_at' => now(),
            'next_attempt_at' => $nextAttemptAt,
            'last_error' => Str::limit($exception->getMessage(), 1000, ''),
        ]);
    }
}
