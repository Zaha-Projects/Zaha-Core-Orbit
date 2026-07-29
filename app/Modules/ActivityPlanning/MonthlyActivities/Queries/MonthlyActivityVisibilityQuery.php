<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class MonthlyActivityVisibilityQuery
{
    public function canViewOtherBranches(?User $user): bool
    {
        return $user !== null
            && ($user->can('monthly_activities.view_other_branches') || $user->hasRole('super_admin'));
    }

    public function scopedBranchIds(?User $user): array
    {
        if (! $this->shouldScopeToUserBranch($user)) {
            return [];
        }

        return method_exists($user, 'scopedBranchIds')
            ? $user->scopedBranchIds()
            : (filled($user?->branch_id) ? [(int) $user->branch_id] : []);
    }

    public function ownBranchId(?User $user): ?int
    {
        return filled($user?->branch_id) ? (int) $user->branch_id : null;
    }

    public function applyDefault(Builder $query, ?User $user): Builder
    {
        if ($this->shouldScopeToUserBranch($user) && $this->ownBranchId($user)) {
            $query->where('branch_id', $this->ownBranchId($user));
        }

        return $query;
    }

    public function applyOtherBranches(Builder $query, ?User $user): Builder
    {
        if ($this->ownBranchId($user)) {
            $query->where('branch_id', '!=', $this->ownBranchId($user));
        }

        return $query;
    }

    public function applyDrafts(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $visibilityQuery) use ($user): void {
            $visibilityQuery->where('status', '!=', 'draft');

            if ($user) {
                $visibilityQuery->orWhere('created_by', $user->id);
            }
        });
    }

    public function applyVolunteerCoordinator(Builder $query, ?User $user): Builder
    {
        if ($user !== null && $user->hasRole('volunteer_coordinator') && ! $user->hasRole('super_admin')) {
            $query->where('needs_volunteers', true);
        }

        return $query;
    }

    private function shouldScopeToUserBranch(?User $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasBranchScopedMonthlyVisibility')
            && $user->hasBranchScopedMonthlyVisibility()
            && ! empty($user->branch_id);
    }
}
