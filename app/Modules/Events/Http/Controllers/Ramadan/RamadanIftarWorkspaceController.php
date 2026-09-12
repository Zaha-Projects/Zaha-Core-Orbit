<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanIftar;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class RamadanIftarWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.view')), 403);
        $query = RamadanIftar::query()->with(['branch', 'relationsOfficer', 'workflowInstance.currentStep.role']);
        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            $branchIds = $user->scopedBranchIds();
            $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchIds);
        }

        $iftars = $query->orderByDesc('planned_date')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('pages.events.ramadan.index', compact('iftars'));
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
        ]);
        $instance = $ramadanIftar->workflowInstance;
        $canCurrentUserApprove = $instance && $user->can('ramadan_iftars.approve')
            && $workflows->currentStepForUser($instance, $user) !== null;
        $canPlan = $ramadanIftar->isPlanningEditable() && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.edit'));
        $canSubmit = $ramadanIftar->isPlanningEditable() && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.submit'));
        $canExecute = $ramadanIftar->canViewExecution() && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute'));
        $canCompleteExecution = $ramadanIftar->closed_at === null
            && $ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_IN_PROGRESS
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute'));
        $canMonitor = $ramadanIftar->status === RamadanIftar::STATUS_APPROVED
            && in_array($ramadanIftar->execution_status, [RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED], true)
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor'));

        return view('pages.events.ramadan.show', compact('ramadanIftar', 'canCurrentUserApprove', 'canPlan', 'canSubmit', 'canExecute', 'canCompleteExecution', 'canMonitor'));
    }
}
