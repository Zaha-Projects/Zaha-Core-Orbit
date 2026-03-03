<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyUsers(Collection $users, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $meta = []): void
    {
        $payload = $users
            ->unique('id')
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'meta' => $meta,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if (! empty($payload)) {
            InAppNotification::insert($payload);
        }
    }
}
