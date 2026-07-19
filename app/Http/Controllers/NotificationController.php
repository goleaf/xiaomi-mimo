<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return Inertia::render('notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->back();
    }
}
