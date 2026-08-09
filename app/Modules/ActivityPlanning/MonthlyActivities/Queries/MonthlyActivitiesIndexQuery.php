<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Queries;

use App\Models\MonthlyActivity;
use App\Models\User;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

    public function summaryCards(Builder $baseQuery): Collection
    {
        $cards = collect([
            [
                'key' => 'total',
                'filter_key' => '',
                'label' => __('app.roles.programs.monthly_activities.list_title'),
                'count' => (clone $baseQuery)->count(),
            ],
            [
                'key' => 'approved',
                'filter_key' => 'approved',
                'label' => __('app.roles.programs.monthly_activities.statuses.approved'),
                'count' => (clone $baseQuery)->where('status', 'approved')->count(),
            ],
        ]);

        $pendingApprovalCards = (clone $baseQuery)
            ->with([
                'workflowInstance.currentStep.role',
                'workflowInstance.currentStep.permission',
            ])
            ->get()
            ->map(fn (MonthlyActivity $activity): ?array => $this->pendingApprovalCardSnapshot($activity))
            ->filter()
            ->groupBy('step_id')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'key' => 'pending-step-' . $first['step_id'],
                    'filter_key' => 'pending_step:' . $first['step_id'],
                    'label' => $first['label'],
                    'count' => $group->count(),
                    'sort_order' => $first['sort_order'],
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->map(fn (array $card): array => Arr::except($card, ['sort_order']));

        return $cards->merge($pendingApprovalCards)->values();
    }

    public function applySummaryFilter(Builder $query, ?string $summaryFilter): void
    {
        $summaryFilter = trim((string) $summaryFilter);

        if ($summaryFilter === '') {
            return;
        }

        if ($summaryFilter === 'approved') {
            $query->where('status', 'approved');

            return;
        }

        if (preg_match('/^pending_step:(\d+)$/', $summaryFilter, $matches) !== 1) {
            return;
        }

        $stepId = (int) ($matches[1] ?? 0);
        if ($stepId <= 0) {
            return;
        }

        $query->whereHas('workflowInstance', function (Builder $workflowQuery) use ($stepId): void {
            $workflowQuery
                ->where('current_step_id', $stepId)
                ->whereNotIn('status', [
                    DynamicWorkflowService::DECISION_APPROVED,
                    DynamicWorkflowService::DECISION_REJECTED,
                    DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
                ]);
        });
    }

    private function pendingApprovalCardSnapshot(MonthlyActivity $activity): ?array
    {
        $instance = $activity->workflowInstance;
        $currentStep = $instance?->currentStep;

        if (! $currentStep || (string) $currentStep->step_type === 'sub') {
            return null;
        }

        if (in_array((string) ($instance?->status ?? ''), [
            DynamicWorkflowService::DECISION_APPROVED,
            DynamicWorkflowService::DECISION_REJECTED,
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
        ], true)) {
            return null;
        }

        $roleLabel = $currentStep->role?->display_name
            ?: ($currentStep->permission?->name
                ? $this->fallbackWorkflowFilterLabel($currentStep->permission->name)
                : ($currentStep->role?->name
                    ? $this->fallbackWorkflowFilterLabel($currentStep->role->name)
                    : null));

        if (! filled($roleLabel)) {
            return null;
        }

        return [
            'step_id' => (int) $currentStep->id,
            'label' => __('workflow_ui.approvals.filters.pending_role', ['role' => $roleLabel]),
            'sort_order' => ((int) $currentStep->step_order * 1000) + (int) ($currentStep->approval_level ?? 0),
        ];
    }

    private function fallbackWorkflowFilterLabel(?string $value): string
    {
        if (! filled($value)) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }
}
