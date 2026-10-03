<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\ActivityNote;
use App\Models\Branch;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\MonthlyActivityApproval;
use App\Modules\Events\Models\MonthlyActivityAttachment;
use App\Models\WorkflowActionLog;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;
use App\Services\WorkflowNotificationService;
use App\Services\MonthlyActivityLifecycleService;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivityApprovals;

class MonthlyActivityApprovalDecisionController extends Controller
{
    use InteractsWithMonthlyActivityApprovals;
    public function update(Request $request, WorkflowNotificationService $workflowNotifications, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycleService, DynamicWorkflowService $dynamicWorkflowService)
    {
        $this->abortIfProgramsManagerViewOnly($request->user());

        $data = $request->validate([
            'decision' => ['nullable', 'string', 'in:approved,approved_final,approved_send_executive,changes_requested,rejected'],
            'comment' => ['nullable', 'string', 'max:2000', 'required_if:decision,changes_requested,rejected'],
            'focus_areas' => ['nullable', 'array', 'required_if:decision,changes_requested,rejected', 'min:1'],
            'focus_areas.*' => ['string', Rule::in(array_keys($this->focusAreaLabels()))],
            'return_url' => ['nullable', 'url'],
            'is_edit_request_implemented' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'coverage_status' => ['nullable', 'string', 'in:not_required,planned,in_progress,completed'],
            'official_correspondence_title' => ['nullable', 'string', 'max:255'],
            'official_correspondence_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $user = $request->user();

        $branchApprovalScope = $this->branchApprovalScope($user);
        if ($branchApprovalScope !== null && ! in_array((int) $monthlyActivity->branch_id, $branchApprovalScope, true)) {
            abort(403);
        }

        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));
        abort_if(! $dynamicWorkflowService->canDecide($instance), 422, __('app.roles.programs.monthly_activities.approvals.errors.not_available_for_current_state'));

        $savedDepartmentNote = $this->storeDepartmentNoteIfPresent($monthlyActivity, $user, $data);

        $step = $dynamicWorkflowService->currentStepForUser($instance, $user);

        if (! $step) {
            abort_if(! $savedDepartmentNote, 403, __('app.roles.programs.monthly_activities.approvals.errors.not_assigned_to_current_step'));

            return redirect()->route('role.programs.approvals.index')->with('status', __('app.roles.programs.monthly_activities.approvals.notes_saved'));
        }

        $dynamicWorkflowService->assertPrerequisites($instance, $step);

        abort_if(empty($data['decision']), 422, __('app.roles.programs.monthly_activities.approvals.errors.decision_required'));

        $decisionComment = $this->formatDecisionComment($data['comment'] ?? null, $data['focus_areas'] ?? []);

        $isRelationsManagerFinalStep = $this->isMonthlyRelationsManagerFinalStep($step->step_key);
        $isFinalApproval = $isRelationsManagerFinalStep && $data['decision'] === 'approved_final';
        $isSendingToExecutive = $isRelationsManagerFinalStep && $data['decision'] === 'approved_send_executive';
        $workflowDecision = in_array($data['decision'], ['approved_final', 'approved_send_executive'], true)
            ? DynamicWorkflowService::DECISION_APPROVED
            : $data['decision'];

        abort_if(
            in_array($data['decision'], ['approved_final', 'approved_send_executive'], true) && ! $isRelationsManagerFinalStep,
            422,
            __('app.roles.programs.monthly_activities.approvals.errors.invalid_decision')
        );

        if ($isRelationsManagerFinalStep && $workflowDecision === DynamicWorkflowService::DECISION_APPROVED) {
            $monthlyActivity->forceFill([
                'executive_review_required' => $isSendingToExecutive,
                'executive_approval_status' => $isSendingToExecutive ? 'pending' : 'skipped',
            ])->save();
        }

        if (
            $request->hasFile('official_correspondence_file')
            && ($user->hasRole('branch_coordinator') || ($user->hasRole('relations_manager') && method_exists($user, 'isKheldaUser') && $user->isKheldaUser()))
            && (bool) $monthlyActivity->needs_official_correspondence
        ) {
            $path = $request->file('official_correspondence_file')->store("events/{$monthlyActivity->id}/official-correspondence", 'public');

            MonthlyActivityAttachment::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'file_type' => 'official_correspondence',
                'title' => $data['official_correspondence_title'] ?: 'المخاطبة الرسمية المعتمدة',
                'file_path' => $path,
                'uploaded_by' => $user->id,
            ]);
        }

        MonthlyActivityApproval::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => $step->step_key,
            'decision' => $workflowDecision,
            'comment' => $decisionComment,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'is_edit_request_implemented' => (bool) ($data['is_edit_request_implemented'] ?? false),
            'implemented_at' => ! empty($data['is_edit_request_implemented']) ? now() : null,
        ]);

        if ($isFinalApproval) {
            $dynamicWorkflowService->recordFinalApproval($instance, $step, $user, $decisionComment);
        } else {
            $dynamicWorkflowService->recordDecision($instance, $step, $user, $workflowDecision, $decisionComment);
        }

        $instance = $instance->fresh();

        $monthlyActivity->update(array_merge([
            'status' => $instance->status === 'changes_requested'
                ? 'changes_requested'
                : ($instance->status === 'approved' ? 'approved' : ($instance->status === 'rejected' ? 'rejected' : 'in_review')),
        ], $this->monthlyLegacyApprovalStatusUpdates((string) $step->step_key, $workflowDecision, $instance->status, $isSendingToExecutive)));

        if ($instance->status === 'approved') {
            $this->publishApprovedLifecycle($monthlyActivity, $lifecycleService);
        }

        $workflowNotifications->approvalDecision(
            $instance,
            $monthlyActivity->fresh('creator'),
            $user,
            $workflowDecision,
            route('role.relations.activities.returned_feedback', ['activity_id' => $monthlyActivity->id]),
            $decisionComment
        );

        WorkflowActionLog::create([
            'module' => 'monthly_activities',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'approval_decision',
            'status' => $workflowDecision,
            'performed_by' => $user->id,
            'meta' => [
                'step' => $step->step_key,
                'submitted_decision' => $data['decision'],
                'comment' => $decisionComment,
                'focus_areas' => $data['focus_areas'] ?? [],
                'iteration' => $instance->edit_request_count,
                'previous_status' => $instance->getOriginal('status'),
                'new_status' => $instance->status,
            ],
            'performed_at' => now(),
        ]);

        return redirect()->to($this->approvalsReturnUrl($data['return_url'] ?? null))
            ->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }

    public function decideExecutionNeed(Request $request, MonthlyActivity $monthlyActivity)
    {
        $this->abortIfProgramsManagerViewOnly($request->user());

        $data = $request->validate([
            'need_key' => ['required', 'string'],
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'required_if:decision,rejected'],
        ]);
        $items = collect($this->executionNeedDecisionItemsForActivity($monthlyActivity, $request->user()))->keyBy('key');
        abort_unless(($items[$data['need_key']]['can_decide'] ?? false), 403);
        $followups = collect($monthlyActivity->execution_needs_followup ?? [])->keyBy(fn ($row) => (string) ($row['key'] ?? ''));
        $followups->put($data['need_key'], [
            'key' => $data['need_key'],
            'status' => $data['decision'] === 'approved' ? 'secured' : 'not_secured',
            'notes' => $data['comment'] ?? null,
            'decision_by_role' => $items[$data['need_key']]['approver'] ?? null,
            'decision_by_name' => $request->user()->name,
        ]);
        $monthlyActivity->update(['execution_needs_followup' => $followups->values()->all()]);
        return redirect()->route('role.programs.approvals.index', ['tab' => 'execution_needs'])->with('status', 'تم تحديث قرار احتياج التنفيذ.');
    }

    protected function approvalsReturnUrl(?string $returnUrl): string
    {
        $fallback = route('role.programs.approvals.index');
        if (! $returnUrl) {
            return $fallback;
        }

        $expected = parse_url($fallback);
        $candidate = parse_url($returnUrl);
        if (
            $candidate === false
            || ($candidate['host'] ?? null) !== ($expected['host'] ?? null)
            || ($candidate['path'] ?? null) !== ($expected['path'] ?? null)
        ) {
            return $fallback;
        }

        return $returnUrl;
    }

    protected function publishApprovedLifecycle(MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycleService): void
    {
        foreach (['Submitted', 'Branch Approved', 'Khelda Liaison Approved', 'Khelda Director Approved', 'Exec Director Approved'] as $target) {
            $monthlyActivity->refresh();

            if ((string) $monthlyActivity->lifecycle_status === 'Exec Director Approved') {
                return;
            }

            if ($lifecycleService->canTransition((string) $monthlyActivity->lifecycle_status, $target)) {
                $lifecycleService->transitionOrFail($monthlyActivity, $target);
            }
        }

        $monthlyActivity->refresh();

        if ((string) $monthlyActivity->lifecycle_status !== 'Exec Director Approved') {
            $monthlyActivity->update(['lifecycle_status' => 'Exec Director Approved']);
        }
    }

    protected function monthlyLegacyApprovalStatusUpdates(string $stepKey, string $decision, string $workflowStatus, bool $sentToExecutive): array
    {
        $updates = [];
        $field = match ($stepKey) {
            'monthly_relations_officer_submit' => 'relations_officer_approval_status',
            'monthly_supervisor_review' => 'relations_manager_approval_status',
            'monthly_branch_coordinator_review' => 'liaison_approval_status',
            'monthly_relations_manager_review' => 'hq_relations_manager_approval_status',
            'monthly_executive_manager_final_approval' => 'executive_approval_status',
            default => null,
        };

        if ($field) {
            $updates[$field] = $decision;
        }

        if ($stepKey === 'monthly_relations_manager_review' && $decision === DynamicWorkflowService::DECISION_APPROVED) {
            $updates['executive_approval_status'] = $sentToExecutive ? 'pending' : 'skipped';
        }

        if ($stepKey === 'monthly_executive_manager_final_approval' && $workflowStatus === DynamicWorkflowService::DECISION_APPROVED) {
            $updates['executive_review_required'] = true;
        }

        return $updates;
    }

    protected function storeDepartmentNoteIfPresent(MonthlyActivity $monthlyActivity, $user, array $data): bool
    {
        $note = trim((string) ($data['note'] ?? ''));
        if ($note === '') {
            return false;
        }

        abort_unless($user->hasRole('workshops_secretary') || $user->hasRole('communication_head'), 403);

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
            'note' => $note,
            'coverage_status' => $role === 'communications' ? ($data['coverage_status'] ?? null) : null,
        ]);

        return true;
    }
}
