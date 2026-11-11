<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Support\MovementPermission;

/** §14.3: individual vehicles (fleet no., plate, per-vehicle status) are manager-only (D7). */
class VehiclePolicy
{
    use ChecksMovementPermission;

    /** Public fleet overview: aggregates only, every active authenticated user (R8, D7). */
    public function viewOverview(User $user): bool { return $user->status === 'active'; }
    public function viewAny(User $user): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function view(User $user, Vehicle $vehicle): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function create(User $user): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function update(User $user, Vehicle $vehicle): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function changeStatus(User $user, Vehicle $vehicle): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function delete(User $user, Vehicle $vehicle): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
    public function restore(User $user, Vehicle $vehicle): bool { return $this->allows($user, MovementPermission::VEHICLES_MANAGE); }
}
