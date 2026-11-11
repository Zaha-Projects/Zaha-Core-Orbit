<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Support\MovementPermission;

class LocationPolicy
{
    use ChecksMovementPermission;

    public function viewAny(User $user): bool { return $this->allows($user, MovementPermission::LOCATIONS_MANAGE); }
    public function create(User $user): bool { return $this->allows($user, MovementPermission::LOCATIONS_MANAGE); }
    public function update(User $user, Location $location): bool { return $this->allows($user, MovementPermission::LOCATIONS_MANAGE); }
    public function delete(User $user, Location $location): bool { return $this->allows($user, MovementPermission::LOCATIONS_MANAGE); }
    public function restore(User $user, Location $location): bool { return $this->allows($user, MovementPermission::LOCATIONS_MANAGE); }
}
