<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanIftar;
use App\Services\DynamicWorkflowService;
use App\Modules\Events\Models\RamadanPeriod;
use Illuminate\Http\Request;

class RamadanIftarWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.view')), 403);
        $query = RamadanIftar::query()->whereDoesntHave('versions')->with(['branch', 'relationsOfficer', 'workflowInstance.currentStep.role'])
            ->with(['monitoringReports' => fn ($query) => $query->latest('updated_at')->latest('id')]);
        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            $branchIds = $user->scopedBranchIds();
            $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchIds);
        }

        $filters = $request->only(['search', 'branch_id', 'status', 'execution_status', 'closure']);
        $query->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        })->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['execution_status'] ?? null), fn ($query) => $query->where('execution_status', $filters['execution_status']))
            ->when(($filters['closure'] ?? null) === 'open', fn ($query) => $query->whereNull('closed_at'))
            ->when(($filters['closure'] ?? null) === 'closed', fn ($query) => $query->whereNotNull('closed_at'));

        $iftars = $query->orderByDesc('planned_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $branches = \App\Models\Branch::query()
            ->when(! $user->hasRole('super_admin') && ! $user->can('branches.view.all'), fn ($query) => $query->whereIn('id', $user->scopedBranchIds()))
            ->orderBy('name')->get();

        return view('pages.events.ramadan.index', compact('iftars', 'branches', 'filters'));
    }

    public function show(Request $request, RamadanIftar $ramadanIftar, DynamicWorkflowService $workflows)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.view')), 403);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id), 403);
        $ramadanIftar->load([
            'branch', 'agendaEvent', 'relationsOfficer', 'communityOrganization', 'localCommunity', 'mobilizationMethod',
            'guidanceVersion', 'targetGroupSelections.targetGroup', 'targetGroupSelections.beneficiarySegment',
            'executionNeeds.executionNeedType', 'meals.items', 'gifts', 'programSegments.executor',
            'executionTeams.leader', 'executionTeams.members.user', 'executionTeams.members.confirmer',
            'volunteerRequirements.beneficiarySegment', 'supplies', 'workflowInstance.currentStep.role',
            'workflowInstance.logs.step', 'workflowInstance.logs.actor', 'monitoringReports.monitoringMethod', 'monitoringReports.monitor',
            'parentVersion', 'versions', 'changeRequests.requester', 'changeRequests.createdVersion',
        ]);
        $instance = $ramadanIftar->workflowInstance;
        $canCurrentUserApprove = $instance && $user->can('ramadan_iftars.approve')
            && $workflows->currentStepForUser($instance, $user) !== null;
        $canPlan = $ramadanIftar->isPlanningEditable() && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.edit'));
        $canSubmit = $ramadanIftar->isPlanningEditable() && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.submit'));
        $canExecute = $ramadanIftar->canViewExecution() && ! ($ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_PLANNED && $ramadanIftar->isSuperseded())
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute'));
        $canCompleteExecution = $ramadanIftar->closed_at === null
            && $ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_IN_PROGRESS
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute'));
        $canMonitor = $ramadanIftar->status === RamadanIftar::STATUS_APPROVED
            && in_array($ramadanIftar->execution_status, [RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED], true)
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor'));
        $canReviewMonitoring = ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor.review'))
            && $ramadanIftar->monitoringReports->contains('status', \App\Modules\Events\Models\MonitoringReport::STATUS_SUBMITTED);
        $closureReadiness = $ramadanIftar->closureReadiness();
        $canClose = ($user->hasRole('super_admin') || ($user->hasRole('supervisor') && $user->can('ramadan_iftars.close')))
            && ($user->hasRole('super_admin') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id))
            && ! in_array(false, $closureReadiness, true);
        $versionHistory = $ramadanIftar->versionHistory();
        $latestVersion = $versionHistory->last();
        $canRequestChange = ($user->hasRole('super_admin') || ($user->hasRole('relations_officer') && $user->can('ramadan_iftars.change_request.create')))
            && ($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id))
            && $ramadanIftar->status === RamadanIftar::STATUS_APPROVED
            && $ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_PLANNED
            && $ramadanIftar->closed_at === null && $latestVersion->id === $ramadanIftar->id
            && ! $ramadanIftar->changeRequests->contains('status', \App\Modules\Events\Models\RamadanIftarChangeRequest::STATUS_PENDING);

        return view('pages.events.ramadan.show', compact('ramadanIftar', 'canCurrentUserApprove', 'canPlan', 'canSubmit', 'canExecute', 'canCompleteExecution', 'canMonitor', 'canReviewMonitoring', 'closureReadiness', 'canClose', 'versionHistory', 'latestVersion', 'canRequestChange'));
    }

    public function calendar(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.view')), 403);
        $request->validate(['year' => ['nullable', 'integer', 'between:2020,2100']]);
        $periods = \App\Modules\Events\Models\RamadanPeriod::query()->orderByDesc('year')->get();
        $query = RamadanIftar::query()->whereDoesntHave('versions');
        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            $query->whereIn('branch_id', $user->scopedBranchIds());
        }
        $recordYears = (clone $query)->selectRaw('YEAR(planned_date) as year')->distinct()->pluck('year');
        $years = $periods->pluck('year')->merge($recordYears)->filter()->unique()->sortDesc()->values();
        $defaultYear = RamadanPeriod::current()?->year ?? now()->year;
        $selectedYear = (int) $request->input('year', $years->contains($defaultYear) ? $defaultYear : ($years->first() ?? $defaultYear));
        $season = $periods->firstWhere('year', $selectedYear);
        $records = $query->whereBetween('planned_date', [$selectedYear.'-01-01', $selectedYear.'-12-31'])
            ->with('branch')->orderBy('planned_date')->orderBy('time_from')->get();
        $iftars = $records->groupBy(fn ($iftar) => $iftar->planned_date->format('Y-m-d'));
        // Historical records remain visible even after an administrator edits or disables the period.
        $dates = $records->pluck('planned_date');
        if ($season) {
            $dates = $dates->merge([$season->start_date, $season->end_date]);
        }
        $period = $dates->isEmpty() ? null : ['start' => $dates->min(), 'end' => $dates->max()];

        return view('pages.events.ramadan.calendar', compact('period', 'iftars', 'periods', 'years', 'selectedYear', 'season'));
    }
}
