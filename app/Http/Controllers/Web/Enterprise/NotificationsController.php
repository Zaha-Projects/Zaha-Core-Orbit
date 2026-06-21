<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;

class NotificationsController extends Controller
{
    public function open(InAppNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->markAsRead();

        return redirect()->to($notification->action_url ?: url()->previous());
    }

    public function markRead(InAppNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->markAsRead();

        return back();
    }
}
