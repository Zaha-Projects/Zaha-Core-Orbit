<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Support\MovementPermission;

/**
 * §14.3 planning, assignment, scheduling and cancellation rows (Revision 6). Permission-based only, active users
 * only, no branch isolation (R5); super_admin passes through Gate::before.
 * - trips.plan: draft trips, stops, times, seats, assignment, pairing, allocation;
 * - trips.publish: schedule and unschedule (publish/unpublish arrive in Phase 6);
 * - trips.cancel: cancel.
 * Public trip pages (Phases 6–7) are not covered here.
 */
class TripPolicy
{
    use ChecksMovementPermission;

    /** The manager's trip list and planner. */
    public function viewAny(User $user): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_PLAN)
            || $this->allows($user, MovementPermission::TRIPS_PUBLISH)
            || $this->allows($user, MovementPermission::TRIPS_CANCEL);
    }

    public function view(User $user, Trip $trip): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_PLAN);
    }

    /** Stops, times, seats, vehicle and driver, pairing and request-leg allocation. */
    public function plan(User $user, Trip $trip): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_PLAN);
    }

    public function schedule(User $user, Trip $trip): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_PUBLISH);
    }

    public function unschedule(User $user, Trip $trip): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_PUBLISH);
    }

    public function cancel(User $user, Trip $trip): bool
    {
        return $this->allows($user, MovementPermission::TRIPS_CANCEL);
    }
}
