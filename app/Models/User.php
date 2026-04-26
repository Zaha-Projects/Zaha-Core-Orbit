<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'branch_id',
        'status',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function assignedBranches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user_assignments')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function deniedPermissions()
    {
        return $this->morphToMany(\Spatie\Permission\Models\Permission::class, 'model', 'model_denied_permissions', 'model_id', 'permission_id');
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $permissionName = is_string($permission) ? $permission : ($permission->name ?? null);

        if ($permissionName && $this->deniedPermissions->pluck('name')->contains($permissionName)) {
            return false;
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function isKheldaUser(): bool
    {
        $branchText = mb_strtolower(trim((string) optional($this->branch)->name . ' ' . (string) optional($this->branch)->city));

        return str_contains($branchText, 'khalda')
            || str_contains($branchText, 'خلدا')
            || str_contains($branchText, 'amman')
            || str_contains($branchText, 'عمان')
            || str_contains($branchText, 'عمّان');
    }

    public function hasBranchScopedMonthlyVisibility(): bool
    {
        if ($this->can('branches.view.all') || $this->isKheldaUser()) {
            return false;
        }

        return $this->can('branches.view.own');
    }

    public function hasBranchScopedAgendaVisibility(): bool
    {
        if ($this->can('branches.view.all') || $this->isKheldaUser()) {
            return false;
        }

        return $this->can('branches.view.own');
    }

    public function isBranchScopedPlanningUser(): bool
    {
        return $this->hasBranchScopedMonthlyVisibility()
            || $this->hasBranchScopedAgendaVisibility();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return array<int, int>
     */
    public function scopedBranchIds(): array
    {
        $assignedIds = $this->relationLoaded('assignedBranches')
            ? $this->assignedBranches->pluck('id')
            : $this->assignedBranches()->pluck('branches.id');

        $ids = $assignedIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        return filled($this->branch_id) ? [(int) $this->branch_id] : [];
    }

    public function primaryScopedBranchId(): ?int
    {
        return $this->scopedBranchIds()[0] ?? null;
    }

    public function hasAccessToScopedBranch(?int $branchId): bool
    {
        if (! $branchId) {
            return false;
        }

        return in_array((int) $branchId, $this->scopedBranchIds(), true);
    }

    /**
     * @return array<int, int>
     */
    public function approvalBranchIds(): array
    {
        return $this->scopedBranchIds();
    }

    public function isAssignedToApprovalBranch(?int $branchId): bool
    {
        if (! $branchId) {
            return false;
        }

        return $this->hasAccessToScopedBranch($branchId);
    }


    public function monthlyActivities()
    {
        return $this->hasMany(MonthlyActivity::class, 'created_by');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'created_by');
    }

    public function bookingsReceived()
    {
        return $this->hasMany(Booking::class, 'received_by');
    }

    public function tripsCreated()
    {
        return $this->hasMany(Trip::class, 'created_by');
    }

    public function inAppNotifications()
    {
        return $this->hasMany(InAppNotification::class);
    }
}
