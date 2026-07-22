<?php

namespace App\Actions;

use App\Models\User;

class MarkNotificationRead
{
    public function handle(User $user, string $notificationId): bool
    {
        return $user->notifications()
            ->whereKey($notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]) === 1;
    }
}
