<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\ActivityNote;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\MonthlyActivityAttachment;
use App\Models\WorkflowActionLog;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;
use App\Services\MonthlyWorkflowPresenter;
use App\Services\WorkflowNotificationService;
use App\Services\MonthlyActivityLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class MonthlyActivitiesApprovalsController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService, MonthlyWorkflowPresenter $monthlyWorkflowPresenter)
    {
        $viewer = $request->user();
        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('monthly_activities', $viewer) || $viewer->can('monthly_activities.approve'),
            403
        );
        $branchCoordinatorScope = $this->branchCoordinatorApprovalScope($viewer);
        $filters = $request->validate([
            'approval_status' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'current_step' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'my_pending' => ['nullable', 'boolean'],
        ]);

        if (($filters['branch_id'] ?? null) && $branchCoordinatorScope !== null && ! in_array((int) $filters['branch_id'], $branchCoordinatorScope, true)) {
            abort(403);
        }

        $activities = MonthlyActivity::query()
            ->with(['approvals.approver', 'creator', 'branch', 'agendaEvent', 'notes.user', 'attachments.uploader', 'workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor'])
            ->whereDoesntHave('newerVersions')
            ->where(function ($query) {
                $query->where('is_from_agenda', false)
                    ->orWhereNull('agenda_event_id')
                    ->orWhereHas('agendaEvent', fn ($agendaQuery) => $agendaQuery->where('event_type', '!=', 'mandatory'));
            })
            ->when($branchCoordinatorScope !== null, function ($query) use ($branchCoordinatorScope) {
                if ($branchCoordinatorScope === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('branch_id', $branchCoordinatorScope);
            })
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['date_from'] ?? null, fn ($q, $dateFrom) => $q->whereDate('proposed_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($q, $dateTo) => $q->whereDate('proposed_date', '<=', $dateTo))
            ->orderByDesc('proposed_date')
            ->paginate(15)
            ->withQueryString();

        $activities->getCollection()->transform(function (MonthlyActivity $activity) use ($dynamicWorkflowService) {
            $instance = $dynamicWorkflowService->forModel('monthly_activities', $activity);
            $activity->setRelation('workflowInstance', $instance);

            return $activity;
        });

        $activities->load('workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor');

        $activities->getCollection()->transform(function (MonthlyActivity $activity) use ($dynamicWorkflowService, $viewer, $monthlyWorkflowPresenter) {
            $instance = $activity->workflowInstance;
            $activity->can_current_user_decide = $instance
                && $dynamicWorkflowService->canDecide($instance)
                && $dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null;
            $activity->can_add_department_note = ($viewer->hasRole('workshops_secretary') && (bool) $activity->requires_workshops)
                || ($viewer->hasRole('communication_head') && (bool) $activity->requires_communications);

            $monthlyWorkflowPresenter->attach($activity, $viewer);

            return $activity;
        });

        $collection = $activities->getCollection();
        if ($viewer->hasRole('workshops_secretary')) {
            $collection = $collection->where('requires_workshops', true)->values();
        }

        if ($viewer->hasRole('communication_head')) {
            $collection = $collection->where('requires_communications', true)->values();
        }

        $workflow = $dynamicWorkflowService->findActiveWorkflow('monthly_activities');
        $workflow?->loadMissing('steps.role', 'steps.permission');
        $statusOptions = $this->buildStatusFilterOptions();
        $currentStepOptions = $this->buildCurrentStepOptions(collect($workflow?->steps ?? []));

        if (! empty($filters['approval_status'])) {
            $collection = $collection->filter(function (MonthlyActivity $activity) use ($filters) {
                return $this->resolveStatusFilterValue($activity) === (string) $filters['approval_status'];
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

        $branches = Branch::query()
            ->when($branchCoordinatorScope !== null, function ($query) use ($branchCoordinatorScope) {
                if ($branchCoordinatorScope === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('id', $branchCoordinatorScope);
            })
            ->orderBy('name')
            ->get();

        $activityCards = $activities->getCollection()
            ->map(fn (MonthlyActivity $activity) => $this->buildActivityCard($activity, $viewer))
            ->values();

        $kpis = [
            'total' => method_exists($activities, 'total') ? $activities->total() : $activities->count(),
            'in_review' => $activityCards->where('status_key', 'in_review')->count(),
            'my_pending' => $activityCards->where('can_current_user_decide', true)->count(),
        ];

        return view('pages.monthly_activities.approvals.index', compact('activities', 'branches', 'filters', 'viewer', 'activityCards', 'kpis', 'statusOptions', 'currentStepOptions'));
    }

    public function details(
        Request $request,
        MonthlyActivity $monthlyActivity,
        DynamicWorkflowService $dynamicWorkflowService,
        MonthlyWorkflowPresenter $monthlyWorkflowPresenter
    ): JsonResponse {
        $viewer = $request->user();
        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('monthly_activities', $viewer) || $viewer->can('monthly_activities.approve'),
            403
        );

        $branchCoordinatorScope = $this->branchCoordinatorApprovalScope($viewer);
        if ($branchCoordinatorScope !== null && ! in_array((int) $monthlyActivity->branch_id, $branchCoordinatorScope, true)) {
            abort(403);
        }

        if (
            (bool) $monthlyActivity->is_from_agenda
            && $monthlyActivity->agenda_event_id
            && optional($monthlyActivity->agendaEvent)->event_type === 'mandatory'
        ) {
            abort(404);
        }

        $monthlyActivity->loadMissing([
            'approvals.approver',
            'creator',
            'branch',
            'agendaEvent',
            'notes.user',
            'attachments.uploader',
            'workflowInstance.currentStep.role',
            'workflowInstance.currentStep.permission',
            'workflowInstance.logs.step',
            'workflowInstance.logs.actor',
        ]);

        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        $monthlyActivity->setRelation('workflowInstance', $instance);
        $monthlyActivity->can_current_user_decide = $instance
            && $dynamicWorkflowService->canDecide($instance)
            && $dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null;
        $monthlyActivity->can_add_department_note = ($viewer->hasRole('workshops_secretary') && (bool) $monthlyActivity->requires_workshops)
            || ($viewer->hasRole('communication_head') && (bool) $monthlyActivity->requires_communications);

        $monthlyWorkflowPresenter->attach($monthlyActivity, $viewer);

        $card = $this->buildActivityCard($monthlyActivity, $viewer);
        $html = view('pages.monthly_activities.approvals.partials.activity-details', compact('card'))->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, WorkflowNotificationService $workflowNotifications, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycleService, DynamicWorkflowService $dynamicWorkflowService)
    {
        $data = $request->validate([
            'decision' => ['nullable', 'string', 'in:approved,approved_final,approved_send_executive,changes_requested,rejected'],
            'comment' => ['nullable', 'string'],
            'is_edit_request_implemented' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'coverage_status' => ['nullable', 'string', 'in:not_required,planned,in_progress,completed'],
            'official_correspondence_title' => ['nullable', 'string', 'max:255'],
            'official_correspondence_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $user = $request->user();

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
            'comment' => $data['comment'] ?? null,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'is_edit_request_implemented' => (bool) ($data['is_edit_request_implemented'] ?? false),
            'implemented_at' => ! empty($data['is_edit_request_implemented']) ? now() : null,
        ]);

        if ($isFinalApproval) {
            $dynamicWorkflowService->recordFinalApproval($instance, $step, $user, $data['comment'] ?? null);
        } else {
            $dynamicWorkflowService->recordDecision($instance, $step, $user, $workflowDecision, $data['comment'] ?? null);
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
            route('role.relations.activities.show', $monthlyActivity)
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
                'comment' => $data['comment'] ?? null,
                'iteration' => $instance->edit_request_count,
                'previous_status' => $instance->getOriginal('status'),
                'new_status' => $instance->status,
            ],
            'performed_at' => now(),
        ]);

        return redirect()->route('role.programs.approvals.index')->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }

    protected function isMonthlyRelationsManagerFinalStep(string $stepKey): bool
    {
        return $stepKey === 'monthly_relations_manager_review';
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

    /**
     * @return array<int, int>|null
     */
    protected function branchCoordinatorApprovalScope($user): ?array
    {
        if (! $user || ! $user->hasRole('branch_coordinator') || $user->can('branches.view.all')) {
            return null;
        }

        return method_exists($user, 'approvalBranchIds')
            ? $user->approvalBranchIds()
            : (filled($user->branch_id) ? [(int) $user->branch_id] : []);
    }

    protected function buildStatusFilterOptions(): Collection
    {
        return collect([
            [
                'value' => 'draft',
                'label' => __('app.roles.programs.monthly_activities.statuses.draft'),
            ],
            [
                'value' => 'submitted',
                'label' => __('app.roles.programs.monthly_activities.statuses.submitted'),
            ],
            [
                'value' => 'approved',
                'label' => __('app.roles.programs.monthly_activities.statuses.approved'),
            ],
        ]);
    }

    protected function buildCurrentStepOptions(Collection $steps): Collection
    {
        return $steps
            ->map(function ($step): ?array {
                $value = (string) ($step->step_key ?? '');
                $label = (string) ($step->name_ar ?: ($step->name_en ?: ''));

                if ($value === '' || $label === '') {
                    return null;
                }

                return ['value' => $value, 'label' => $label];
            })
            ->filter()
            ->unique('value')
            ->values();
    }

    protected function resolveStatusFilterValue(MonthlyActivity $activity): string
    {
        $status = (string) data_get($activity, 'workflow_summary.status_key', $activity->status ?? 'draft');

        return match ($status) {
            'draft' => 'draft',
            'approved' => 'approved',
            default => 'submitted',
        };
    }

    protected function buildActivityCard(MonthlyActivity $activity, $viewer): array
    {
        $workflowSummary = $activity->workflow_summary ?? [];
        $workflowSteps = collect($workflowSummary['steps'] ?? [])->values();
        $logs = collect($workflowSummary['timeline'] ?? [])->values();
        $approvedStepsCount = $workflowSteps->where('state', 'approved')->count();
        $totalStepsCount = max($workflowSteps->count(), 1);
        $statusKey = ($workflowSummary['status_key'] ?? '') ?: (($workflowSummary['workflow_state'] ?? '') ?: (optional($activity->workflowInstance)->status ?: 'pending'));

        $requirements = [];
        if ($activity->requires_programs) {
            $requirements[] = __('workflow_ui.approvals.requirements.programs');
        }
        if ($activity->requires_workshops) {
            $requirements[] = __('workflow_ui.approvals.requirements.workshops');
        }
        if ($activity->requires_communications) {
            $requirements[] = __('workflow_ui.approvals.requirements.communications');
        }

        $attachments = $activity->attachments
            ->where('file_type', 'official_correspondence')
            ->map(function ($attachment) {
                $isExternal = filter_var($attachment->file_path, FILTER_VALIDATE_URL);

                return [
                    'title' => $attachment->title ?: __('workflow_ui.approvals.official.view_attachment'),
                    'url' => $isExternal
                        ? $attachment->file_path
                        : route('role.programs.attachments.download', $attachment),
                ];
            })
            ->values()
            ->all();

        $canUploadOfficialCorrespondence = $viewer
            && method_exists($viewer, 'hasRole')
            && ($viewer->hasRole('branch_coordinator') || ($viewer->hasRole('relations_manager') && method_exists($viewer, 'isKheldaUser') && $viewer->isKheldaUser()))
            && (bool) $activity->needs_official_correspondence;

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'branch_name' => optional($activity->branch)->name ?? '-',
            'date_label' => sprintf('%02d-%02d', (int) $activity->month, (int) $activity->day),
            'submitted_by_name' => $workflowSummary['submitted_by_name'] ?? '-',
            'submitted_at' => $workflowSummary['submitted_at'] ?? null,
            'status_key' => $statusKey,
            'status_class' => 'wf-status-'.$statusKey,
            'status_label' => $workflowSummary['status_label'] ?? __('workflow_ui.common.none_option'),
            'current_step_label' => $workflowSummary['current_step_label'] ?? __('workflow_ui.common.unknown_step'),
            'current_role_label' => $workflowSummary['current_role_label'] ?? __('workflow_ui.common.none_option'),
            'completed_steps_count' => (int) ($workflowSummary['completed_steps_count'] ?? 0),
            'total_steps_count' => (int) ($workflowSummary['total_steps_count'] ?? 0),
            'workflow_steps_count' => (int) $workflowSteps->count(),
            'approved_steps_count' => $approvedStepsCount,
            'progress_percentage' => round(($approvedStepsCount / $totalStepsCount) * 100, 2),
            'requirements' => $requirements,
            'workflow_steps' => $workflowSteps->map(function ($step) {
                return [
                    'label' => $step['label'] ?? '-',
                    'role_label' => $step['role_label'] ?? '-',
                    'state' => $step['state'] ?? 'pending',
                    'state_label' => $step['state_label'] ?? '-',
                    'actor_name' => $step['actor_name'] ?? null,
                    'acted_at' => $step['acted_at'] ?? null,
                    'comment' => $step['comment'] ?? null,
                    'is_current' => (bool) ($step['is_current'] ?? false),
                ];
            })->all(),
            'logs' => $logs->map(function ($entry) {
                return [
                    'step_label' => $entry['step_label'] ?? '-',
                    'role_label' => $entry['role_label'] ?? '-',
                    'actor_name' => $entry['actor_name'] ?? '-',
                    'acted_at' => $entry['acted_at'] ?? null,
                    'comment' => $entry['comment'] ?? null,
                    'action' => $entry['action'] ?? 'pending',
                    'action_label' => $entry['action_label'] ?? '-',
                ];
            })->all(),
            'latest_change_request' => $workflowSummary['latest_change_request'] ?? null,
            'needs_official_correspondence' => (bool) $activity->needs_official_correspondence,
            'official_correspondence' => [
                'target' => $activity->official_correspondence_target,
                'reason' => $activity->official_correspondence_reason,
                'brief' => $activity->official_correspondence_brief,
                'attachments' => $attachments,
            ],
            'decision_options' => $this->decisionOptionsForStep((string) optional(optional($activity->workflowInstance)->currentStep)->step_key),
            'permissions' => [
                'can_decide' => (bool) ($workflowSummary['can_current_user_decide'] ?? $activity->can_current_user_decide ?? false),
                'can_add_department_note' => (bool) ($activity->can_add_department_note ?? false),
                'can_upload_official_correspondence' => $canUploadOfficialCorrespondence,
                'show_coverage_status' => $viewer && method_exists($viewer, 'hasRole') && $viewer->hasRole('communication_head'),
            ],
            'can_current_user_decide' => (bool) ($workflowSummary['can_current_user_decide'] ?? $activity->can_current_user_decide ?? false),
            'update_url' => route('role.programs.approvals.update', $activity),
            'details_url' => route('role.programs.approvals.details', $activity),
        ];
    }

    protected function decisionOptionsForStep(string $stepKey): array
    {
        if ($this->isMonthlyRelationsManagerFinalStep($stepKey)) {
            return [
                [
                    'value' => 'approved_final',
                    'label' => __('workflow_ui.approvals.decisions.approved_final'),
                ],
                [
                    'value' => 'approved_send_executive',
                    'label' => __('workflow_ui.approvals.decisions.approved_send_executive'),
                ],
                [
                    'value' => 'changes_requested',
                    'label' => __('workflow_ui.approvals.status_labels.changes_requested'),
                ],
                [
                    'value' => 'rejected',
                    'label' => __('workflow_ui.approvals.status_labels.rejected'),
                ],
            ];
        }

        return [
            [
                'value' => 'approved',
                'label' => __('workflow_ui.approvals.status_labels.approved'),
            ],
            [
                'value' => 'changes_requested',
                'label' => __('workflow_ui.approvals.status_labels.changes_requested'),
            ],
            [
                'value' => 'rejected',
                'label' => __('workflow_ui.approvals.status_labels.rejected'),
            ],
        ];
    }
}
