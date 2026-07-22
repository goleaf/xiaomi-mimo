<?php

namespace App\Actions;

use App\Enums\ReminderStatus;
use App\Models\Reminder;

class CancelReminder
{
    public function handle(Reminder $reminder, ?string $claimToken = null): bool
    {
        $query = Reminder::query()->whereKey($reminder->id);

        if ($claimToken === null) {
            $query->whereIn('status', [ReminderStatus::Pending, ReminderStatus::Failed]);
        } else {
            $query->where('status', ReminderStatus::Processing)
                ->where('claim_token', $claimToken);
        }

        return $query->update([
            'status' => ReminderStatus::Cancelled,
            'claim_token' => null,
            'claimed_at' => null,
            'next_attempt_at' => null,
            'cancelled_at' => now(),
        ]) === 1;
    }
}
