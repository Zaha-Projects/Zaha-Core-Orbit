<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    protected function normalizeMeta(array $meta): ?string
    {
        if (empty($meta)) {
            return null;
        }

        $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

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
                'meta' => $this->normalizeMeta($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if (! empty($payload)) {
            InAppNotification::insert($payload);
        }
    }
}
