<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Support\MovementPermission;

/** §14.3: driver names, phones and licences are manager-only (D7); management needs drivers.manage. */
class DriverPolicy
{
    use ChecksMovementPermission;

    public function viewAny(User $user): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE) || $this->allows($user, MovementPermission::DRIVERS_VIEW_DETAILS); }
    public function view(User $user, Driver $driver): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE); }
    public function update(User $user, Driver $driver): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE); }
    public function link(User $user, Driver $driver): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE); }
    public function delete(User $user, Driver $driver): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE); }
    public function restore(User $user, Driver $driver): bool { return $this->allows($user, MovementPermission::DRIVERS_MANAGE); }
}
