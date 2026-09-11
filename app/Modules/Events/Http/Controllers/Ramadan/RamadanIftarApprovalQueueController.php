<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use App\Modules\Events\Models\RamadanIftar;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class RamadanIftarApprovalQueueController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $workflows)
    {
        $user = $request->user();
        $this->authorizeCapability($user);
        $workflow = $workflows->findActiveWorkflow(RamadanIftar::WORKFLOW_MODULE);
        abort_unless($workflow, 404);
        $workflow->loadMissing('steps.role');
        $stepIds = $user->hasRole('super_admin')
            ? $workflow->steps->where('step_type', 'main')->pluck('id')->all()
            : $workflow->steps->where('step_type', 'main')->filter(function ($step) use ($user) {
                return $step->role && $user->hasRole($step->role->name);
            })->pluck('id')->all();

        $query = RamadanIftar::query()
            ->with(['branch', 'relationsOfficer', 'workflowInstance.currentStep.role'])
            ->where('status', RamadanIftar::STATUS_SUBMITTED)
            ->whereExists(function ($query) use ($workflow, $stepIds) {
                $query->selectRaw('1')->from('workflow_instances')
                    ->whereColumn('workflow_instances.entity_id', 'ramadan_iftars.id')
                    ->where('workflow_instances.workflow_id', $workflow->id)
                    ->where('workflow_instances.entity_type', RamadanIftar::class)
                    ->whereIn('workflow_instances.status', ['pending', 'in_progress'])
                    ->whereIn('workflow_instances.current_step_id', $stepIds);
            });

        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            $branchIds = $user->scopedBranchIds();
            $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchIds);
        }

        $iftars = $query->orderBy('planned_date')->orderBy('id')->paginate(15)->withQueryString();

        return view('pages.events.ramadan.approvals.index', compact('iftars'));
    }

    public function show(Request $request, RamadanIftar $ramadanIftar, DynamicWorkflowService $workflows)
    {
        $user = $request->user();
        $this->authorizeCapability($user);
        $this->authorizeBranch($user, $ramadanIftar);
        $instance = $ramadanIftar->workflowInstance()->with(['workflow', 'currentStep.role', 'logs.step', 'logs.actor'])->first();
        abort_unless($instance && $instance->workflow->module === RamadanIftar::WORKFLOW_MODULE && $workflows->currentStepForUser($instance, $user), 403);
        $ramadanIftar->load([
            'branch', 'agendaEvent', 'relationsOfficer', 'guidanceVersion', 'targetGroupSelections.targetGroup',
            'targetGroupSelections.beneficiarySegment', 'executionNeeds.executionNeedType', 'meals.items', 'gifts',
            'programSegments', 'executionTeams.members', 'volunteerRequirements', 'supplies',
        ]);

        return view('pages.events.ramadan.approvals.show', ['ramadanIftar' => $ramadanIftar, 'workflowInstance' => $instance]);
    }

    private function authorizeCapability($user): void
    {
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.approve')), 403);
    }

    private function authorizeBranch($user, RamadanIftar $iftar): void
    {
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
    }
}
