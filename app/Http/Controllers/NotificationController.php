<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * 通知一覧画面を表示する。
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする。
     */
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        $this->authorize('read', $notification);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知が既読されました');
    }
}
