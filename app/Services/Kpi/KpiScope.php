<?php

namespace App\Services\Kpi;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who may see the KPI section and which branches their figures may include.
 *
 * Oversight (all branches): super_admin or branches.view.all. Everyone else is limited to
 * scopedBranchIds(); a user with no scoped branch sees no data rather than global totals.
 */
class KpiScope
{
    /** @var array<int, int>|null null = all branches */
    private ?array $allowedBranchIds;

    private ?int $selectedBranchId;

    private function __construct(?array $allowedBranchIds, ?int $selectedBranchId)
    {
        $this->allowedBranchIds = $allowedBranchIds;
        $this->selectedBranchId = $selectedBranchId;
    }

    /** Mirrors the kpi.* route middleware (role_or_permission:super_admin|kpi.view). */
    public static function canAccess(?User $user): bool
    {
        return $user !== null && ($user->hasRole('super_admin') || $user->can('kpi.view'));
    }

    /** Cross-user performance viewing (Phase 5). Never implied by kpi.view. */
    public static function canViewUsers(?User $user): bool
    {
        return $user !== null && ($user->hasRole('super_admin') || $user->can('kpi.users.view'));
    }

    /**
     * Self always; others only with kpi.users.view AND the target inside the viewer's branch scope
     * (oversight viewers: any user). Prevents direct-ID access to out-of-scope users.
     */
    public static function canViewUser(?User $viewer, User $target): bool
    {
        if ($viewer === null) {
            return false;
        }
        if ((int) $viewer->id === (int) $target->id) {
            return true;
        }
        if (! self::canViewUsers($viewer)) {
            return false;
        }

        return self::hasOversight($viewer) || array_intersect($viewer->scopedBranchIds(), $target->scopedBranchIds()) !== [];
    }

    /** Organisational report exports (Phase 5). Own performance export does not need it. */
    public static function canExport(?User $user): bool
    {
        return $user !== null && ($user->hasRole('super_admin') || $user->can('kpi.export'));
    }

    /** Self view: the user's own actions in every branch (user filter applied by the caller). */
    public static function forSelf(User $user): self
    {
        return new self(null, null);
    }

    public static function hasOversight(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('branches.view.all');
    }

    /**
     * @param  int|null  $requestedBranchId  already validated as a positive integer
     */
    public static function forUser(User $user, ?int $requestedBranchId): self
    {
        $allowed = self::hasOversight($user) ? null : $user->scopedBranchIds();

        if ($requestedBranchId !== null) {
            abort_unless($allowed === null || in_array($requestedBranchId, $allowed, true), 403);
            abort_unless(Branch::query()->whereKey($requestedBranchId)->exists(), 404);
        }

        return new self($allowed, $requestedBranchId);
    }

    /** Effective branch filter for queries: null = all branches. */
    public function branchIds(): ?array
    {
        if ($this->selectedBranchId !== null) {
            return [$this->selectedBranchId];
        }

        return $this->allowedBranchIds;
    }

    public function isAllBranches(): bool
    {
        return $this->branchIds() === null;
    }

    public function hasNoBranches(): bool
    {
        return $this->branchIds() === [];
    }

    /** @return array<int, int>|null branches the viewer may see (null = all) */
    public function allowedBranchIds(): ?array
    {
        return $this->allowedBranchIds;
    }

    public function selectedBranchId(): ?int
    {
        return $this->selectedBranchId;
    }

    /** Branches the viewer may pick in the filter. */
    public function availableBranches(): Collection
    {
        return Branch::query()
            ->when($this->allowedBranchIds !== null, fn ($query) => $query->whereIn('id', $this->allowedBranchIds ?: [0]))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function label(): string
    {
        if ($this->selectedBranchId !== null) {
            return (string) (Branch::query()->whereKey($this->selectedBranchId)->value('name') ?? '—');
        }

        if ($this->allowedBranchIds === null) {
            return 'جميع الفروع';
        }

        return $this->allowedBranchIds === [] ? 'لا توجد فروع ضمن نطاقك' : 'الفروع ضمن نطاقك';
    }
}
