<?php

namespace App\Actions;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Reminder;
use App\Models\UserPreference;
use App\Notifications\ReminderNotification;
use Illuminate\Support\Facades\DB;

class DeliverClaimedReminder
{
    public function __construct(private CancelReminder $cancelReminder) {}

    public function handle(string $reminderId, string $claimToken): void
    {
        $reminder = Reminder::query()
            ->with(['todo', 'user.preferences'])
            ->whereKey($reminderId)
            ->where('status', ReminderStatus::Processing)
            ->where('claim_token', $claimToken)
            ->first();

        if (! $reminder instanceof Reminder) {
            return;
        }

        if ($reminder->todo === null || ! $this->channelEnabled($reminder)) {
            $this->cancelReminder->handle($reminder, $claimToken);

            return;
        }

        if ($reminder->type === ReminderType::Email) {
            $preferences = $reminder->user->getRelation('preferences');
            $locale = $preferences instanceof UserPreference ? $preferences->language : 'en';
            $reminder->user->notify(
                (new ReminderNotification($reminder))->locale($locale),
            );
            $this->markDelivered($reminder, $claimToken);

            return;
        }

        DB::transaction(function () use ($reminder, $claimToken): void {
            $claimed = Reminder::query()
                ->whereKey($reminder->id)
                ->where('status', ReminderStatus::Processing)
                ->where('claim_token', $claimToken)
                ->lockForUpdate()
                ->first();

            if (! $claimed instanceof Reminder) {
                return;
            }

            $notification = new ReminderNotification($reminder);
            $reminder->user->notifications()->firstOrCreate(
                ['id' => $reminder->id],
                [
                    'type' => ReminderNotification::class,
                    'data' => $notification->databaseData(),
                    'read_at' => null,
                ],
            );
            $this->markDelivered($claimed, $claimToken);
        }, 5);
    }

    private function channelEnabled(Reminder $reminder): bool
    {
        $preferences = $reminder->user->getRelation('preferences');
        $defaults = UserPreference::defaults();

        return match ($reminder->type) {
            ReminderType::Email => $preferences instanceof UserPreference
                ? $preferences->notification_email
                : $defaults['notification_email'],
            ReminderType::Browser => $preferences instanceof UserPreference
                ? $preferences->notification_browser
                : $defaults['notification_browser'],
            ReminderType::InApp => $preferences instanceof UserPreference
                ? $preferences->notification_in_app
                : $defaults['notification_in_app'],
        };
    }

    private function markDelivered(Reminder $reminder, string $claimToken): void
    {
        Reminder::query()
            ->whereKey($reminder->id)
            ->where('status', ReminderStatus::Processing)
            ->where('claim_token', $claimToken)
            ->update([
                'status' => ReminderStatus::Delivered,
                'is_sent' => true,
                'claim_token' => null,
                'claimed_at' => null,
                'next_attempt_at' => null,
                'delivered_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);
    }
}
