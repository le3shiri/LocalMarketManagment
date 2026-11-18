<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /** Open a notice: mark it read and go to its order. */
    public function show(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $code = $notification->data['order_code'] ?? null;

        return match (true) {
            $code !== null => redirect()->route('orders.show', $code),
            // Only internal links stored by our own notifications.
            isset($notification->data['url']) && str_starts_with($notification->data['url'], url('/')) => redirect()->to($notification->data['url']),
            default => redirect()->route('notifications.index'),
        };
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Avisos marcados como leídos.');
    }
}
