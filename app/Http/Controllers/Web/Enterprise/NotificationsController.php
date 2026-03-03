<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;

class NotificationsController extends Controller
{
    public function markRead(InAppNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['read_at' => now()]);

        return back();
    }
}
