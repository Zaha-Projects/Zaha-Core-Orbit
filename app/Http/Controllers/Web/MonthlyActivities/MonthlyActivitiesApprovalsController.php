<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\ActivityNote;
use App\Models\Branch;
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
        $viewer = $request->user();
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,in_progress,approved,changes_requested,rejected'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'assignee' => ['nullable', 'string'],
            'current_step' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'my_pending' => ['nullable', 'boolean'],
        ]);

        $activities = MonthlyActivity::query()
            ->with(['approvals.approver', 'creator', 'branch', 'notes.user', 'workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor'])
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['date_from'] ?? null, fn ($q, $dateFrom) => $q->whereDate('proposed_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($q, $dateTo) => $q->whereDate('proposed_date', '<=', $dateTo))
            ->orderByDesc('proposed_date')
            ->paginate(15)
            ->withQueryString();

        $activities->getCollection()->transform(function (MonthlyActivity $activity) use ($dynamicWorkflowService) {
            $dynamicWorkflowService->forModel('monthly_activities', $activity);
            return $activity;
        });

        $activities->load('workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor');

        $collection = $activities->getCollection();
        if ($viewer->hasRole('workshops_secretary')) {
            $collection = $collection->where('requires_workshops', true)->values();
        }

        if ($viewer->hasRole('communication_head')) {
            $collection = $collection->where('requires_communications', true)->values();
        }

        if (! empty($filters['status'])) {
            $collection = $collection->filter(fn (MonthlyActivity $activity) => optional($activity->workflowInstance)->status === $filters['status'])->values();
        }

        if (! empty($filters['assignee'])) {
            $collection = $collection->filter(function (MonthlyActivity $activity) use ($filters) {
                $step = optional($activity->workflowInstance)->currentStep;
                $candidate = $step?->role?->name ?? $step?->permission?->name;

                return $candidate === $filters['assignee'];
            })->values();
        }

        if (! empty($filters['current_step'])) {
            $collection = $collection->filter(fn (MonthlyActivity $activity) => optional(optional($activity->workflowInstance)->currentStep)->step_key === $filters['current_step'])->values();
        }

        if (! empty($filters['my_pending'])) {
            $collection = $collection->filter(function (MonthlyActivity $activity) use ($dynamicWorkflowService, $viewer) {
                $instance = $activity->workflowInstance;
                if (! $instance || ! $dynamicWorkflowService->canDecide($instance)) {
                    return false;
                }

                return $dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null;
            })->values();
        }

        $activities->setCollection($collection);

        $branches = Branch::query()->orderBy('name')->get();

        return view('pages.monthly_activities.approvals.index', compact('activities', 'branches', 'filters'));
    }

    public function update(Request $request, NotificationService $notifications, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycleService, DynamicWorkflowService $dynamicWorkflowService)
    {
        $data = $request->validate([
            'decision' => ['nullable', 'string', 'in:approved,changes_requested,rejected'],
            'comment' => ['nullable', 'string'],
            'is_edit_request_implemented' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'coverage_status' => ['nullable', 'string', 'in:not_required,planned,in_progress,completed'],
        ]);

        $user = $request->user();

        if ($user->hasRole('workshops_secretary') || $user->hasRole('communication_head')) {
            $this->storeDepartmentNote($monthlyActivity, $user, $data);

            return redirect()->route('role.programs.approvals.index')->with('status', __('app.roles.programs.monthly_activities.approvals.notes_saved'));
        }

        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));
        abort_if(! $dynamicWorkflowService->canDecide($instance), 422, __('app.roles.programs.monthly_activities.approvals.errors.not_available_for_current_state'));

        $step = $dynamicWorkflowService->currentStepForUser($instance, $user);
        abort_unless($step !== null, 403, __('app.roles.programs.monthly_activities.approvals.errors.not_assigned_to_current_step'));
        abort_if((int) $monthlyActivity->created_by === (int) $user->id, 422, __('app.roles.programs.monthly_activities.approvals.errors.self_approval_forbidden'));

        $dynamicWorkflowService->assertPrerequisites($instance, $step);

        abort_if(empty($data['decision']), 422, __('app.roles.programs.monthly_activities.approvals.errors.decision_required'));

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
        $instance = $instance->fresh();

        $monthlyActivity->update([
            'status' => $instance->status === 'changes_requested'
                ? 'changes_requested'
                : ($instance->status === 'approved' ? 'approved' : ($instance->status === 'rejected' ? 'rejected' : 'in_review')),
        ]);

        if ($instance->status === 'approved') {
            $lifecycleService->transitionOrFail($monthlyActivity, 'Exec Director Approved');
        }

        $notifications->notifyUsers(
            collect([$monthlyActivity->creator])->filter(),
            'approval_decision',
            __('app.roles.programs.monthly_activities.approvals.notifications.title'),
            __('app.roles.programs.monthly_activities.approvals.notifications.body', ['decision' => $data['decision'], 'step' => $step->step_key]),
            route('role.programs.approvals.index')
        );

        WorkflowActionLog::create([
            'module' => 'monthly_activities',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $user->id,
            'meta' => [
                'step' => $step->step_key,
                'comment' => $data['comment'] ?? null,
                'iteration' => $instance->edit_request_count,
                'previous_status' => $instance->getOriginal('status'),
                'new_status' => $instance->status,
            ],
            'performed_at' => now(),
        ]);

        return redirect()->route('role.programs.approvals.index')->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }

    protected function storeDepartmentNote(MonthlyActivity $monthlyActivity, $user, array $data): void
    {
        abort_if(empty(trim((string) ($data['note'] ?? ''))), 422, __('app.roles.programs.monthly_activities.approvals.errors.note_required'));

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
