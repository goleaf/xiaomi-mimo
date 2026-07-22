<?php

namespace App\Actions;

use App\Models\User;

class MarkAllNotificationsRead
{
    public function handle(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
