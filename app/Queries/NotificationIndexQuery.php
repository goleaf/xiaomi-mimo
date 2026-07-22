<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationIndexQuery
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function forUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
