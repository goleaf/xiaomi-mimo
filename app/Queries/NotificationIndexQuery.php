<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationIndexQuery
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function forUser(User $user, string $status = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $notifications = $user->notifications()
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        foreach ($notifications->getCollection() as $notification) {
            $todoId = $notification->data['todo_id'] ?? null;
            $notification->setAttribute(
                'url',
                is_string($todoId) && $todoId !== ''
                    ? route('todos.show', ['todo' => $todoId])
                    : null,
            );
        }

        return $notifications;
    }

    /** @return array{total: int, unread: int, read: int} */
    public function statsForUser(User $user): array
    {
        $stats = $user->notifications()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN read_at IS NULL THEN 1 END) AS unread')
            ->first();
        $total = (int) ($stats?->getAttribute('total') ?? 0);
        $unread = (int) ($stats?->getAttribute('unread') ?? 0);

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $total - $unread,
        ];
    }
}
