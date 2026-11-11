<?php

namespace App\Modules\Movement\Policies;

use App\Models\User;

/**
 * Shared permission check for Movement policies: permission-based only (no role checks),
 * the user must be active (plan §14.1), and per-user denials win through User::hasPermissionTo.
 * super_admin is allowed earlier by Gate::before (AuthServiceProvider).
 */
trait ChecksMovementPermission
{
    protected function allows(User $user, string $permission): bool
    {
        return $user->status === 'active' && $user->checkPermissionTo($permission);
    }
}
