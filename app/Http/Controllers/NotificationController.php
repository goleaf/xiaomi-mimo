<?php

namespace App\Http\Controllers;

use App\Actions\MarkAllNotificationsRead;
use App\Actions\MarkNotificationRead;
use App\Http\Requests\NotificationIndexRequest;
use App\Models\User;
use App\Queries\NotificationIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(
        NotificationIndexRequest $request,
        NotificationIndexQuery $notificationIndexQuery,
    ): Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return Inertia::render('notifications/Index', [
            'notifications' => $notificationIndexQuery->forUser(
                $user,
                $request->status(),
                $request->perPage(),
            ),
            'stats' => $notificationIndexQuery->statsForUser($user),
            'filters' => [
                'status' => $request->status(),
                'per_page' => $request->perPage(),
            ],
        ]);
    }

    public function markRead(
        Request $request,
        string $id,
        MarkNotificationRead $markNotificationRead,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $markNotificationRead->handle($user, $id);

        return redirect()->back();
    }

    public function markAllRead(
        Request $request,
        MarkAllNotificationsRead $markAllNotificationsRead,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $markAllNotificationsRead->handle($user);

        return redirect()->back();
    }
}
