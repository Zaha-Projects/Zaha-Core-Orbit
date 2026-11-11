<?php

namespace App\Modules\Movement\Services;

use App\Models\User;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Support\MovementPermission;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

/**
 * Recipient resolution for Movement notifications (plan §15.5, Revision 5). In-app only, via
 * NotificationService::notifyUsers. Text comes from resources/lang/{ar,en}/movement.php.
 */
class MovementNotifier
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Active users who can review requests. Spatie's permission() scope returns role and direct holders
     * but ignores per-user denials (User::hasPermissionTo), so every candidate is re-checked.
     */
    public function managers(): Collection
    {
        return User::permission(MovementPermission::REQUESTS_REVIEW)->get()
            ->filter(fn (User $user) => $user->status === 'active' && $user->hasPermissionTo(MovementPermission::REQUESTS_REVIEW))
            ->values();
    }

    public function requestSubmitted(MovementRequest $request, bool $resubmitted): void
    {
        $this->send($this->managers(), 'movement.request.submitted', $resubmitted ? 'resubmitted' : 'submitted', $request);
    }

    public function requestDecided(MovementRequest $request): void
    {
        $this->send($this->requester($request), 'movement.request.decided', $request->status, $request);
    }

    public function requestCancelled(MovementRequest $request, User $actor): void
    {
        // A requester's cancel informs the managers; a manager's cancel informs the requester (§8.4).
        $recipients = $actor->id === $request->requester_id ? $this->managers() : $this->requester($request);
        $this->send($recipients->reject(fn (User $user) => $user->id === $actor->id), 'movement.request.cancelled', 'cancelled', $request);
    }

    private function requester(MovementRequest $request): Collection
    {
        return collect([$request->requester])->filter();
    }

    private function send(Collection $users, string $type, string $event, MovementRequest $request): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $params = ['reference' => $request->reference_no];
        $titleKey = 'movement.notifications.'.$event.'.title';
        $messageKey = 'movement.notifications.'.$event.'.message';

        // Same i18n meta convention as WorkflowNotificationService: the menu renders in the viewer's locale.
        $this->notifications->notifyUsers(
            $users,
            $type,
            __($titleKey, $params),
            __($messageKey, $params),
            route('movement.requests.show', $request),
            [
                'module' => 'movement',
                'request_id' => $request->id,
                'reference_no' => $request->reference_no,
                'event' => $event,
                'i18n' => ['title_key' => $titleKey, 'message_key' => $messageKey, 'replace' => $params, 'translated_replace_keys' => []],
            ]
        );
    }
}
