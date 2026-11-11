<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Support\MovementPermission;

/** Vehicle windows need vehicles.manage; driver leave needs drivers.manage (§14.1). */
class UnavailabilityPolicy
{
    use ChecksMovementPermission;

    public function createFor(User $user, string $subject): bool
    {
        return $this->allows($user, $subject === 'driver' ? MovementPermission::DRIVERS_MANAGE : MovementPermission::VEHICLES_MANAGE);
    }

    public function delete(User $user, Unavailability $window): bool
    {
        return $this->createFor($user, $window->driver_id !== null ? 'driver' : 'vehicle');
    }
}
