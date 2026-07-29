<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Queries;

use App\Models\MonthlyActivity;
use App\Models\User;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MonthlyActivitiesIndexQuery
{
    private MonthlyActivityVisibilityQuery $visibility;

    public function __construct(MonthlyActivityVisibilityQuery $visibility)
    {
        $this->visibility = $visibility;
    }

    public function build(Request $request, MonthlyActivityListFilters $filters): Builder
    {
        $user = $request->user();

        if ($filters->viewScope === 'all_branches' && ! $this->visibility->canViewOtherBranches($user)) {
            abort(403);
        }

        $query = MonthlyActivity::query()
            ->when($filters->showDeleted, fn (Builder $builder) => $builder->onlyTrashed())
            ->withCount('newerVersions')
            ->whereDoesntHave('newerVersions')
            ->enterpriseFilter($request->except(['status', 'year', 'month', 'per_page', 'branch_id']))
            ->notArchived();

        $this->applyMonth($query, $filters->year, $filters->month);

        if ($filters->branchId) {
            $query->where('branch_id', $filters->branchId);
        }

        if ($filters->viewScope !== 'all_branches') {
            $this->visibility->applyDefault($query, $user);
        }

        $this->visibility->applyDrafts($query, $user);
        $this->visibility->applyVolunteerCoordinator($query, $user);
        $this->applyStatus($query, $filters->status);

        if ($filters->viewScope === 'all_branches') {
            $this->applyPublishedOtherBranches($query, $user);
        }

        return $query;
    }

    public function buildDeletedCount(MonthlyActivityListFilters $filters, ?User $user): Builder
    {
        $query = MonthlyActivity::query()
            ->onlyTrashed()
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $this->applyMonth($query, $filters->year, $filters->month);

        if ($filters->branchId) {
            $query->where('branch_id', $filters->branchId);
        }

        if ($filters->viewScope !== 'all_branches') {
            $this->visibility->applyDefault($query, $user);
        }

        return $query;
    }

    public function applyMonth(Builder $query, int $year, int $month): void
    {
        $query->where(function (Builder $dateQuery) use ($year, $month): void {
            $dateQuery
                ->where(function (Builder $proposedDateQuery) use ($year, $month): void {
                    $proposedDateQuery
                        ->whereNotNull('proposed_date')
                        ->whereYear('proposed_date', $year)
                        ->whereMonth('proposed_date', $month);
                })
                ->orWhere(function (Builder $fallbackMonthQuery) use ($month): void {
                    $fallbackMonthQuery
                        ->whereNull('proposed_date')
                        ->where('month', $month);
                });
        });
    }

    public function applyStatus(Builder $query, ?string $status): void
    {
        $status = trim((string) $status);

        if ($status === '') {
            return;
        }

        $query->where(function (Builder $statusQuery) use ($status): void {
            match ($status) {
                'draft' => $statusQuery->where('status', 'draft'),
                'approved' => $statusQuery->whereIn('status', ['approved']),
                'submitted' => $statusQuery->whereIn('status', ['submitted', 'pending', 'in_review', 'changes_requested', 'rejected', 'postponed', 'cancelled', 'closed', 'completed', 'executed']),
                default => $statusQuery->where('status', $status),
            };
        });
    }

    public function applyPublishedOtherBranches(Builder $query, ?User $user): void
    {
        $this->visibility->applyOtherBranches($query, $user);

        $query
            ->where('status', 'approved')
            ->where(function (Builder $approvalQuery): void {
                $approvalQuery
                    ->where('executive_approval_status', 'approved')
                    ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                    ->orWhereHas('workflowInstance', fn (Builder $workflowQuery) => $workflowQuery->where('status', DynamicWorkflowService::DECISION_APPROVED));
            });
    }
}
