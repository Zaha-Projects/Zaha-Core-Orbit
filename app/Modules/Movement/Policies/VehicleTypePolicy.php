<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementPermission;

class VehicleTypePolicy
{
    use ChecksMovementPermission;

    public function viewAny(User $user): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function create(User $user): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function update(User $user, VehicleType $type): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function delete(User $user, VehicleType $type): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function restore(User $user, VehicleType $type): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
}
