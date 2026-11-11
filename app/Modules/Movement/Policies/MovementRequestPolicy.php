<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Support\MovementPermission;

/**
 * §14.3 request rows (Revision 5). Requesters act on their own requests only; supervisors see own
 * requests only (D12). Managers need view_all / review. Active users only; no branch isolation (R5).
 */
class MovementRequestPolicy
{
    use ChecksMovementPermission;

    /** "My requests". */
    public function viewAny(User $user): bool
    {
        // Users who lost the create permission still see the requests they made earlier.
        return $this->allows($user, MovementPermission::REQUESTS_CREATE)
            || ($user->status === 'active' && MovementRequest::query()->where('requester_id', $user->id)->exists());
    }

    public function view(User $user, MovementRequest $request): bool
    {
        return ($user->status === 'active' && $this->owns($user, $request))
            || $this->allows($user, MovementPermission::REQUESTS_VIEW_ALL)
            || $this->allows($user, MovementPermission::REQUESTS_REVIEW);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MovementPermission::REQUESTS_CREATE);
    }

    public function update(User $user, MovementRequest $request): bool
    {
        return $this->owns($user, $request) && $this->allows($user, MovementPermission::REQUESTS_CREATE);
    }

    public function submit(User $user, MovementRequest $request): bool
    {
        return $this->update($user, $request);
    }

    public function delete(User $user, MovementRequest $request): bool
    {
        return $this->update($user, $request);
    }

    /** Requester (own) or manager (§8.4). */
    public function cancel(User $user, MovementRequest $request): bool
    {
        return $this->update($user, $request) || $this->allows($user, MovementPermission::REQUESTS_REVIEW);
    }

    /** Review queue and decisions. */
    public function review(User $user): bool
    {
        return $this->allows($user, MovementPermission::REQUESTS_REVIEW);
    }

    public function decide(User $user, MovementRequest $request): bool
    {
        return $this->allows($user, MovementPermission::REQUESTS_REVIEW);
    }

    private function owns(User $user, MovementRequest $request): bool
    {
        return (int) $request->requester_id === (int) $user->id;
    }
}
