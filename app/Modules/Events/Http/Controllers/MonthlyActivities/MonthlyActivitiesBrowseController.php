<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivitiesBrowseController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function index(Request $request)
    {
        $user = $request->user();
        $viewScope = $request->input('scope', 'default');
        $selectedStatus = trim((string) $request->input('status', ''));
        $selectedBranchId = filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $selectedSummaryFilter = trim((string) $request->input('summary_filter', ''));
        $selectedYear = $this->normalizeMonthlyIndexYear($request->input('year'));
        $selectedMonth = $this->normalizeMonthlyIndexMonth($request->input('month'));
        $showDeleted = $request->boolean('deleted');

        if ($request->routeIs('followup.monthly-plans')) {
            $selectedBranchId = $this->followupOfficerBranchId($user);
            $viewScope = 'default';
        }

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($user)) {
            abort(403);
        }

        $activitiesBaseQuery = MonthlyActivity::query()
            ->when($showDeleted, fn ($query) => $query->onlyTrashed())
            ->withCount('newerVersions')
            ->whereDoesntHave('newerVersions')
            ->enterpriseFilter($request->except(['status', 'year', 'month', 'per_page', 'branch_id']))
            ->notArchived();

        $this->applyMonthlyPageMonthFilter($activitiesBaseQuery, $selectedYear, $selectedMonth);
        if ($selectedBranchId) {
            $activitiesBaseQuery->where('branch_id', $selectedBranchId);
        }

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($activitiesBaseQuery, $user);
        }
        $this->applyDraftVisibilityScope($activitiesBaseQuery, $user);
        $this->applyVolunteerCoordinatorVisibilityScope($activitiesBaseQuery, $user);
        $this->applyMonthlyPageStatusFilter($activitiesBaseQuery, $selectedStatus);

        if ($viewScope === 'all_branches') {
            $this->applyOtherBranchesScope($activitiesBaseQuery, $user);

            $activitiesBaseQuery
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $deletedActivitiesCount = (clone $activitiesBaseQuery)->toBase()->cloneWithout(['orders', 'limit', 'offset'])->count();
        if (! $showDeleted) {
            $deletedCountQuery = MonthlyActivity::query()->onlyTrashed()->whereDoesntHave('newerVersions')->notArchived();
            $this->applyMonthlyPageMonthFilter($deletedCountQuery, $selectedYear, $selectedMonth);
            if ($selectedBranchId) { $deletedCountQuery->where('branch_id', $selectedBranchId); }
            if ($viewScope !== 'all_branches') { $this->applyBranchVisibilityScope($deletedCountQuery, $user); }
            $deletedActivitiesCount = $deletedCountQuery->count();
        }

        $summaryCards = $showDeleted ? collect() : $this->buildMonthlyIndexSummaryCards($activitiesBaseQuery);
        $this->applyMonthlyIndexSummaryFilter($activitiesBaseQuery, $selectedSummaryFilter);

        $allowedPerPage = [8, 16, 24, 50, 100];
        $perPage = (int) $request->input('per_page', 8);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 8;
        }

        $activities = (clone $activitiesBaseQuery)
            ->with([
                'branch',
                'agendaEvent',
                'creator',
            ])
            ->orderBy('month')
            ->orderBy('day')
            ->paginate($perPage)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        $ownBranchId = $this->ownBranchId($user);
        if ($scopedBranchIds !== [] && $viewScope !== 'all_branches') {
            $branches->where('id', $ownBranchId);
        }
        if ($viewScope === 'all_branches' && $ownBranchId) {
            $branches->where('id', '!=', $ownBranchId);
        }
        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $filters = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'status' => $selectedStatus,
            'branch_id' => $selectedBranchId,
            'summary_filter' => $selectedSummaryFilter,
            'deleted' => $showDeleted,
            'per_page' => $perPage,
        ];
        $selectedMonthDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $previousMonthQuery = collect($request->except(['page', 'year', 'month', 'per_page']))
            ->put('year', $selectedMonthDate->copy()->subMonthNoOverflow()->year)
            ->put('month', $selectedMonthDate->copy()->subMonthNoOverflow()->month)
            ->all();
        $nextMonthQuery = collect($request->except(['page', 'year', 'month', 'per_page']))
            ->put('year', $selectedMonthDate->copy()->addMonthNoOverflow()->year)
            ->put('month', $selectedMonthDate->copy()->addMonthNoOverflow()->month)
            ->all();
        $canFilterBranches = ! $request->routeIs('followup.monthly-plans')
            && ($viewScope === 'all_branches'
                ? $this->canViewOtherBranches($user)
                : ($scopedBranchIds === []));

        $monthlyStatusOptions = $this->monthlyPageStatusOptions();
        $monthlyActivityEditRoles = $this->monthlyActivityEditRoles();
        $monthlyActivityChangeRequestRoles = $this->monthlyActivityChangeRequestRoles();

        return view('pages.monthly_activities.activities.index', compact(
            'activities',
            'branches',
            'agendaEvents',
            'filters',
            'canFilterBranches',
            'viewScope',
            'monthlyStatusOptions',
            'summaryCards',
            'monthlyActivityEditRoles',
            'monthlyActivityChangeRequestRoles',
            'selectedMonthDate',
            'previousMonthQuery',
            'nextMonthQuery',
            'showDeleted',
            'deletedActivitiesCount',
        ));
    }

    protected function monthlyPageStatusOptions(): Collection
    {
        return collect([
            (object) ['code' => 'draft', 'name' => __('app.roles.programs.monthly_activities.statuses.draft')],
            (object) ['code' => 'submitted', 'name' => __('app.roles.programs.monthly_activities.statuses.submitted')],
            (object) ['code' => 'post_execution_submitted', 'name' => 'بانتظار اعتماد رئيس الفرع لما بعد التنفيذ'],
            (object) ['code' => 'approved', 'name' => __('app.roles.programs.monthly_activities.statuses.approved')],
        ]);
    }

    protected function applyMonthlyIndexSummaryFilter(Builder $query, ?string $summaryFilter): void
    {
        $summaryFilter = trim((string) $summaryFilter);

        if ($summaryFilter === '') {
            return;
        }

        if ($summaryFilter === 'approved') {
            $query->where('status', 'approved');

            return;
        }

        if (preg_match('/^pending_step:(\d+)$/', $summaryFilter, $matches) === 1) {
            $stepId = (int) ($matches[1] ?? 0);

            if ($stepId <= 0) {
                return;
            }

            $query->whereHas('workflowInstance', function ($workflowQuery) use ($stepId) {
                $workflowQuery
                    ->where('current_step_id', $stepId)
                    ->whereNotIn('status', [
                        DynamicWorkflowService::DECISION_APPROVED,
                        DynamicWorkflowService::DECISION_REJECTED,
                        DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
                    ]);
            });
        }
    }

    protected function buildMonthlyIndexSummaryCards(Builder $baseQuery): Collection
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

        // Group pending activities by the live workflow step that is currently waiting for approval.
        $pendingApprovalCards = (clone $baseQuery)
            ->with([
                'workflowInstance.currentStep.role',
                'workflowInstance.currentStep.permission',
            ])
            ->get()
            ->map(fn (MonthlyActivity $activity): ?array => $this->resolvePendingApprovalCardSnapshot($activity))
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

    protected function resolvePendingApprovalCardSnapshot(MonthlyActivity $activity): ?array
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

    protected function fallbackWorkflowFilterLabel(?string $value): string
    {
        if (! filled($value)) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }
}
