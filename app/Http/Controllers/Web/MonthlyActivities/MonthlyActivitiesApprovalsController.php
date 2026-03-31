<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\ActivityNote;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\WorkflowActionLog;
use App\Models\WorkflowLog;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Services\DynamicWorkflowService;
use App\Services\MonthlyActivityWorkflowService;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\MonthlyActivityLifecycleService;

class MonthlyActivitiesApprovalsController extends Controller
{
    public function index(Request $request, MonthlyActivityWorkflowService $workflowService, DynamicWorkflowService $dynamicWorkflowService)
    {
        $activities = MonthlyActivity::with(['approvals.approver', 'creator', 'notes.user', 'workflowInstance.currentStep'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $viewer = $request->user();

        if ($viewer->hasRole('workshops_secretary')) {
            $activities = $activities->where('requires_workshops', true)->values();
        }

        if ($viewer->hasRole('communication_head')) {
            $activities = $activities->where('requires_communications', true)->values();
        }

        $stepLabels = $activities
            ->flatMap(fn (MonthlyActivity $activity) => $workflowService->buildStepLabelMap($activity))
            ->all();

        $workflow = $dynamicWorkflowService->findActiveWorkflow('monthly_activity_approval');
        if ($workflow) {
            foreach ($activities as $activity) {
                $dynamicWorkflowService->forEntity($workflow, MonthlyActivity::class, $activity->id);
            }
            $activities->load('workflowInstance.currentStep');
        }

        return view('pages.monthly_activities.approvals.index', compact('activities', 'stepLabels'));
    }

    public function update(Request $request, NotificationService $notifications, MonthlyActivity $monthlyActivity, MonthlyActivityWorkflowService $workflowService, MonthlyActivityLifecycleService $lifecycleService, DynamicWorkflowService $dynamicWorkflowService)
    {
        $data = $request->validate([
            'decision' => ['nullable', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
            'is_edit_request_implemented' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'coverage_status' => ['nullable', 'string', 'in:not_required,planned,in_progress,completed'],
        ]);

        $user = $request->user();

        if ($user->hasRole('workshops_secretary') || $user->hasRole('communication_head')) {
            $this->storeDepartmentNote($monthlyActivity, $user, $data);

            return redirect()
                ->route('role.programs.approvals.index')
                ->with('status', __('تم حفظ الملاحظة بنجاح.'));
        }

        $step = $workflowService->currentStepForUser($monthlyActivity, $user);
        abort_unless($step !== null, 403);
        $workflowService->assertPrerequisites($monthlyActivity, $step['key']);

        abort_if(empty($data['decision']), 422, __('Decision is required'));

        MonthlyActivityApproval::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => $step['key'],
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'is_edit_request_implemented' => (bool) ($data['is_edit_request_implemented'] ?? false),
            'implemented_at' => ! empty($data['is_edit_request_implemented']) ? now() : null,
        ]);

        $workflow = $dynamicWorkflowService->findActiveWorkflow('monthly_activity_approval');
        if ($workflow) {
            $instance = $dynamicWorkflowService->forEntity($workflow, MonthlyActivity::class, $monthlyActivity->id);
            WorkflowLog::query()->create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $instance->current_step_id,
                'acted_by' => $user->id,
                'action' => $data['decision'],
                'comment' => $data['comment'] ?? null,
                'edit_request_iteration' => $instance->edit_request_count,
                'acted_at' => now(),
            ]);

            if ($data['decision'] === 'changes_requested') {
                $instance->increment('edit_request_count');
                $instance->update(['status' => 'changes_requested']);
            } else {
                $dynamicWorkflowService->advanceToNextStep($instance->fresh());
            }
        }

        $updates = [
            $step['status_field'] => $data['decision'],
            'status' => $data['decision'] === 'approved' ? 'in_review' : 'changes_requested',
        ];

        if ($step['key'] === 'executive_review') {
            $updates['status'] = $data['decision'] === 'approved' ? 'approved' : 'changes_requested';
        }

        if ($data['decision'] === 'changes_requested') {
            $updates = array_merge($updates, $workflowService->rollbackToBranchForChanges($monthlyActivity, $step['key']));
        }

        $monthlyActivity->update($updates);

        if ($data['decision'] === 'approved') {
            $stepLifecycleMap = [
                'branch_relations_officer_review' => 'Branch Approved',
                'hq_liaison_review' => 'Khelda Liaison Approved',
                'hq_relations_manager_review' => 'Khelda Director Approved',
                'executive_review' => 'Exec Director Approved',
            ];

            if (isset($stepLifecycleMap[$step['key']])) {
                $lifecycleService->transitionOrFail($monthlyActivity, $stepLifecycleMap[$step['key']]);
            }
        }

        $notifications->notifyUsers(collect([$monthlyActivity->creator])->filter(), 'approval_decision', 'Approval update', 'Decision: '. $data['decision'], route('role.programs.approvals.index'));

        WorkflowActionLog::create([
            'module' => 'monthly_activity',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $user->id,
            'meta' => ['step' => $step['key']],
            'performed_at' => now(),
        ]);

        return redirect()
            ->route('role.programs.approvals.index')
            ->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }

    protected function storeDepartmentNote(MonthlyActivity $monthlyActivity, $user, array $data): void
    {
        abort_if(empty(trim((string) ($data['note'] ?? ''))), 422, __('Note is required'));

        if ($user->hasRole('workshops_secretary')) {
            $role = 'workshops';
            abort_unless((bool) $monthlyActivity->requires_workshops, 403);
        } else {
            $role = 'communications';
            abort_unless((bool) $monthlyActivity->requires_communications, 403);
        }

        ActivityNote::create([
            'activity_id' => $monthlyActivity->id,
            'user_id' => $user->id,
            'role' => $role,
            'note' => trim((string) $data['note']),
            'coverage_status' => $role === 'communications' ? ($data['coverage_status'] ?? null) : null,
        ]);
    }
}
