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
