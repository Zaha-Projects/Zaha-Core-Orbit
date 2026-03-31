<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\ActivityNote;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\WorkflowActionLog;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Services\MonthlyActivityLifecycleService;

class MonthlyActivitiesApprovalsController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService)
    {
        $activities = MonthlyActivity::with(['approvals.approver', 'creator', 'notes.user', 'workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor'])
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

        foreach ($activities as $activity) {
            $dynamicWorkflowService->forModel('monthly_activities', $activity);
        }
        $activities->load('workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor');

        return view('pages.monthly_activities.approvals.index', compact('activities'));
    }

    public function update(Request $request, NotificationService $notifications, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycleService, DynamicWorkflowService $dynamicWorkflowService)
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

            return redirect()->route('role.programs.approvals.index')->with('status', __('تم حفظ الملاحظة بنجاح.'));
        }

        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, 'No active workflow for monthly_activities module');

        $step = $dynamicWorkflowService->currentStepForUser($instance, $user);
        abort_unless($step !== null, 403);
        $dynamicWorkflowService->assertPrerequisites($instance, $step);

        abort_if(empty($data['decision']), 422, __('Decision is required'));

        MonthlyActivityApproval::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => $step->step_key,
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'is_edit_request_implemented' => (bool) ($data['is_edit_request_implemented'] ?? false),
            'implemented_at' => ! empty($data['is_edit_request_implemented']) ? now() : null,
        ]);

        $dynamicWorkflowService->recordDecision($instance, $step, $user, $data['decision'], $data['comment'] ?? null);

        $monthlyActivity->update([
            'status' => $data['decision'] === 'approved' ? (($instance->fresh()->status === 'approved') ? 'approved' : 'in_review') : 'changes_requested',
        ]);

        if ($data['decision'] === 'approved' && $instance->fresh()->status === 'approved') {
            $lifecycleService->transitionOrFail($monthlyActivity, 'Exec Director Approved');
        }

        $notifications->notifyUsers(collect([$monthlyActivity->creator])->filter(), 'approval_decision', 'Approval update', 'Decision: '. $data['decision'], route('role.programs.approvals.index'));

        WorkflowActionLog::create([
            'module' => 'monthly_activity',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $user->id,
            'meta' => ['step' => $step->step_key],
            'performed_at' => now(),
        ]);

        return redirect()->route('role.programs.approvals.index')->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
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
