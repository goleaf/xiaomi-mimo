<?php

namespace App\Actions;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimDueReminders
{
    /** @return Collection<int, Reminder> */
    public function handle(int $limit): Collection
    {
        return DB::transaction(function () use ($limit): Collection {
            $now = now();
            $staleAt = $now->copy()->subMinutes(Reminder::CLAIM_LEASE_MINUTES);
            $candidates = Reminder::query()
                ->where('reminded_at', '<=', $now)
                ->where('attempts', '<', Reminder::MAX_ATTEMPTS)
                ->where(function ($query) use ($now, $staleAt): void {
                    $query->where('status', ReminderStatus::Pending)
                        ->orWhere(function ($failed) use ($now): void {
                            $failed->where('status', ReminderStatus::Failed)
                                ->whereNotNull('next_attempt_at')
                                ->where('next_attempt_at', '<=', $now);
                        })
                        ->orWhere(function ($processing) use ($staleAt): void {
                            $processing->where('status', ReminderStatus::Processing)
                                ->where('claimed_at', '<=', $staleAt);
                        });
                })
                ->orderBy('reminded_at')
                ->orderBy('id')
                ->limit(max(1, min($limit, 500)))
                ->get();
            $claimed = new Collection;

            foreach ($candidates as $reminder) {
                $attributes = [
                    'status' => ReminderStatus::Processing,
                    'claim_token' => (string) Str::uuid(),
                    'attempts' => $reminder->attempts + 1,
                    'claimed_at' => $now,
                    'next_attempt_at' => null,
                    'failed_at' => null,
                    'last_error' => null,
                ];
                $updated = Reminder::query()
                    ->whereKey($reminder->id)
                    ->where('status', $reminder->status)
                    ->where('attempts', $reminder->attempts)
                    ->update($attributes);

                if ($updated === 1) {
                    $reminder->forceFill($attributes)->syncOriginal();
                    $claimed->push($reminder);
                }
            }

            return $claimed;
        }, 5);
    }
}
