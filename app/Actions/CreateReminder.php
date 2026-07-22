<?php

namespace App\Actions;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;

class CreateReminder
{
    public function handle(Todo $todo, User $user, string $remindedAt, string $type = 'in_app'): Reminder
    {
        return $todo->reminders()->create([
            'user_id' => $user->id,
            'reminded_at' => $remindedAt,
            'type' => $type,
            'status' => ReminderStatus::Pending,
            'is_sent' => false,
            'attempts' => 0,
        ]);
    }
}
