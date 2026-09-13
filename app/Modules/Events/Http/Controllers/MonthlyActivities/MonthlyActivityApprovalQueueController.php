<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;
use App\Services\MonthlyWorkflowPresenter;
use App\Services\PlanChangeRequestWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivityApprovals;

class MonthlyActivityApprovalQueueController extends Controller
{
    use InteractsWithMonthlyActivityApprovals;
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService, MonthlyWorkflowPresenter $monthlyWorkflowPresenter, PlanChangeRequestWorkflowService $changeRequests)
    {
        $viewer = $request->user();
        $this->abortIfProgramsManagerViewOnly($viewer);

        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('monthly_activities', $viewer) || $viewer->can('monthly_activities.approve'),
            403
        );
        $branchApprovalScope = $this->branchApprovalScope($viewer);
        $filters = $request->validate([
            'approval_status' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'current_step' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'my_pending' => ['nullable', 'boolean'],
        ]);

        if (($filters['branch_id'] ?? null) && $branchApprovalScope !== null && ! in_array((int) $filters['branch_id'], $branchApprovalScope, true)) {
            abort(403);
        }

        $activitiesQuery = MonthlyActivity::query()
            ->with(['approvals.approver', 'creator', 'branch', 'agendaEvent', 'notes.user', 'attachments.uploader', 'workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission', 'workflowInstance.logs.step', 'workflowInstance.logs.actor'])
            ->whereDoesntHave('newerVersions')
            ->where(function ($query) {
                $query->where('is_from_agenda', false)
                    ->orWhereNull('agenda_event_id')
                    ->orWhereHas('agendaEvent', fn ($agendaQuery) => $agendaQuery->where('event_type', '!=', 'mandatory'));
            })
            ->when($branchApprovalScope !== null, function ($query) use ($branchApprovalScope) {
                if ($branchApprovalScope === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('branch_id', $branchApprovalScope);
            })
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['date_from'] ?? null, fn ($q, $dateFrom) => $q->whereDate('proposed_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($q, $dateTo) => $q->whereDate('proposed_date', '<=', $dateTo))
            ->when($viewer->hasRole('workshops_secretary'), fn ($query) => $query->where('requires_workshops', true))
            ->when($viewer->hasRole('communication_head'), fn ($query) => $query->where('requires_communications', true));

        $this->applyWorkflowApprovalFilters($activitiesQuery, $filters, $viewer);

        $activities = $activitiesQuery
            ->orderByDesc('proposed_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        if ($activities->total() > 0 && $activities->currentPage() > $activities->lastPage()) {
            return redirect()->route('role.programs.approvals.index', array_merge(
                $request->except('page'),
                ['page' => $activities->lastPage()]
            ));
        }

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

        $workflow = $dynamicWorkflowService->findActiveWorkflow('monthly_activities');
        $workflow?->loadMissing('steps.role', 'steps.permission');
        $statusOptions = $this->buildStatusFilterOptions();
        $currentStepOptions = $this->buildCurrentStepOptions(collect($workflow?->steps ?? []));

        $activities->setCollection($collection);

        $branches = Branch::query()
            ->when($branchApprovalScope !== null, function ($query) use ($branchApprovalScope) {
                if ($branchApprovalScope === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('id', $branchApprovalScope);
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

        $deleteRequestsBase = MonthlyPlanDeleteRequest::query()
            ->with(['requester', 'currentApprover', 'monthlyActivity.branch', 'workflowInstance.currentStep.role', 'workflowInstance.workflow.steps'])
            ->when($branchApprovalScope !== null, fn ($query) => $branchApprovalScope === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchApprovalScope));
        $this->applyChangeRequestFilters($deleteRequestsBase, $filters);
        $deleteRequests = (clone $deleteRequestsBase)
            ->latest()
            ->paginate(10, ['*'], 'delete_page')
            ->withQueryString();
        $editRequestsBase = MonthlyPlanEditRequest::query()
            ->with(['requester', 'currentApprover', 'monthlyActivity.branch', 'workflowInstance.currentStep.role', 'workflowInstance.workflow.steps'])
            ->when($branchApprovalScope !== null, fn ($query) => $branchApprovalScope === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchApprovalScope));
        $this->applyChangeRequestFilters($editRequestsBase, $filters);
        $editRequests = (clone $editRequestsBase)
            ->latest()
            ->paginate(10, ['*'], 'edit_page')
            ->withQueryString();

        $deleteRequests->getCollection()->transform(function (MonthlyPlanDeleteRequest $deleteRequest) use ($dynamicWorkflowService, $viewer, $changeRequests) {
            $instance = $changeRequests->normalizeRequesterSubmission($deleteRequest, 'monthly_activities');
            $deleteRequest->load('requester', 'currentApprover');
            $deleteRequest->setRelation('workflowInstance', $instance);
            $stepForViewer = $instance ? $dynamicWorkflowService->currentStepForUser($instance, $viewer) : null;
            $deleteRequest->workflow_timeline = $changeRequests->workflowTimelineForRequest($deleteRequest, $instance);
            $deleteRequest->can_current_user_decide = $instance
                && $dynamicWorkflowService->canDecide($instance)
                && ($stepForViewer !== null || (int) ($deleteRequest->current_approver_id ?? 0) === (int) $viewer->id);

            return $deleteRequest;
        });
        $editRequests->getCollection()->transform(function (MonthlyPlanEditRequest $editRequest) use ($dynamicWorkflowService, $viewer, $changeRequests) {
            $instance = $changeRequests->normalizeRequesterSubmission($editRequest, 'monthly_activities');
            $editRequest->load('requester', 'currentApprover');
            $editRequest->setRelation('workflowInstance', $instance);
            $stepForViewer = $instance ? $dynamicWorkflowService->currentStepForUser($instance, $viewer) : null;
            $editRequest->workflow_timeline = $changeRequests->workflowTimelineForRequest($editRequest, $instance);
            $editRequest->can_current_user_decide = $instance
                && $dynamicWorkflowService->canDecide($instance)
                && ($stepForViewer !== null || (int) ($editRequest->current_approver_id ?? 0) === (int) $viewer->id);

            return $editRequest;
        });

        if (! empty($filters['my_pending'])) {
            $deleteRequests->setCollection($deleteRequests->getCollection()->filter(fn (MonthlyPlanDeleteRequest $request) => (bool) ($request->can_current_user_decide ?? false))->values());
            $editRequests->setCollection($editRequests->getCollection()->filter(fn (MonthlyPlanEditRequest $request) => (bool) ($request->can_current_user_decide ?? false))->values());
        }

        $postExecutionBase = MonthlyActivity::query()
            ->with(['creator', 'branch', 'attachments.uploader', 'supplies', 'team', 'workflowInstance.currentStep.role', 'workflowInstance.logs.step', 'workflowInstance.logs.actor'])
            ->where('status', 'post_execution_submitted')
            ->when($branchApprovalScope !== null, fn ($query) => $branchApprovalScope === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchApprovalScope));
        $this->applyMonthlyActivityApprovalFilters($postExecutionBase, $filters, 'actual_date');
        $postExecutionApprovals = (clone $postExecutionBase)
            ->latest('updated_at')
            ->paginate(10, ['*'], 'post_execution_page')
            ->withQueryString();
        $postExecutionApprovals->getCollection()->transform(function (MonthlyActivity $activity) use ($viewer) {
            $activity->can_current_user_decide = $this->canReviewPostExecution($activity, $viewer);
            $activity->workflow_timeline = $this->workflowTimelineForActivity($activity);
            return $activity;
        });

        $executionNeedsBase = MonthlyActivity::query()
            ->with(['creator', 'branch'])
            ->whereNotNull('execution_needs_payload')
            ->where(function ($query) {
                $query->whereNull('execution_needs_followup')
                    ->orWhereJsonLength('execution_needs_followup', 0)
                    ->orWhere('execution_needs_followup', '!=', '[]');
            })
            ->when($branchApprovalScope !== null, fn ($query) => $branchApprovalScope === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchApprovalScope));
        $this->applyMonthlyActivityApprovalFilters($executionNeedsBase, $filters, 'proposed_date');
        $executionNeedsDecisions = (clone $executionNeedsBase)
            ->latest('updated_at')
            ->paginate(10, ['*'], 'execution_needs_page')
            ->withQueryString();
        $executionNeedsDecisions->getCollection()->transform(function (MonthlyActivity $activity) use ($viewer) {
            $activity->execution_need_decision_items = $this->executionNeedDecisionItemsForActivity($activity, $viewer);
            $activity->can_current_user_decide = collect($activity->execution_need_decision_items)->contains('can_decide', true);
            $activity->workflow_timeline = $this->workflowTimelineForActivity($activity);
            return $activity;
        });
        $executionNeedsDecisions->setCollection($executionNeedsDecisions->getCollection()->filter(fn (MonthlyActivity $activity) => collect($activity->execution_need_decision_items)->isNotEmpty())->values());

        if (! empty($filters['my_pending'])) {
            $postExecutionApprovals->setCollection($postExecutionApprovals->getCollection()->filter(fn (MonthlyActivity $activity) => (bool) ($activity->can_current_user_decide ?? false))->values());
            $executionNeedsDecisions->setCollection($executionNeedsDecisions->getCollection()->filter(fn (MonthlyActivity $activity) => (bool) ($activity->can_current_user_decide ?? false))->values());
        }

        $tabCounts = [
            'approval' => $activities->getCollection()->count(),
            'delete' => $deleteRequests->getCollection()->count(),
            'edit' => $editRequests->getCollection()->count(),
            'post_execution' => $postExecutionApprovals->getCollection()->count(),
            'execution_needs' => $executionNeedsDecisions->getCollection()->count(),
        ];
        $adminRequestStats = $viewer->hasRole('super_admin') ? $this->monthlyChangeRequestStats($filters, $branchApprovalScope) : null;

        return view('pages.monthly_activities.approvals.index', compact('activities', 'branches', 'filters', 'viewer', 'activityCards', 'kpis', 'statusOptions', 'currentStepOptions', 'deleteRequests', 'editRequests', 'postExecutionApprovals', 'executionNeedsDecisions', 'tabCounts', 'adminRequestStats'));
    }

    public function details(
        Request $request,
        MonthlyActivity $monthlyActivity,
        DynamicWorkflowService $dynamicWorkflowService,
        MonthlyWorkflowPresenter $monthlyWorkflowPresenter
    ): JsonResponse {
        $viewer = $request->user();
        $this->abortIfProgramsManagerViewOnly($viewer);

        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('monthly_activities', $viewer) || $viewer->can('monthly_activities.approve'),
            403
        );

        $branchApprovalScope = $this->branchApprovalScope($viewer);
        if ($branchApprovalScope !== null && ! in_array((int) $monthlyActivity->branch_id, $branchApprovalScope, true)) {
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
            'targetGroups',
            'sponsors',
            'partners',
            'supplies',
            'team',
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

        if ($request->query('view') === 'activity') {
            $statusLabel = $this->monthlyActivityStatusLabel((string) $monthlyActivity->status);
            $executionStatusLabel = $this->executionStatusLabel($monthlyActivity->executionStatusForDisplay());
            $html = view('pages.monthly_activities.approvals.partials.activity-summary-modal', compact('monthlyActivity', 'statusLabel', 'executionStatusLabel'))->render();

            return response()->json(['html' => $html]);
        }

        $card = $this->buildActivityCard($monthlyActivity, $viewer);
        $html = view('pages.monthly_activities.approvals.partials.activity-details', compact('card'))->render();

        return response()->json(['html' => $html]);
    }

    protected function executionStatusLabel(string $status): string
    {
        return match ($status) {
            'planned' => 'بانتظار التنفيذ',
            'executed' => 'منفذة',
            'postponed' => 'مؤجلة',
            'cancelled' => 'ملغية',
            default => $status ?: '-',
        };
    }

    protected function monthlyActivityStatusLabel(string $status): string
    {
        $translated = __('app.roles.programs.monthly_activities.statuses.' . $status);

        return $translated !== 'app.roles.programs.monthly_activities.statuses.' . $status
            ? $translated
            : ($status ?: '-');
    }

    protected function monthlyChangeRequestStats(array $filters, ?array $branchApprovalScope): array
    {
        $build = function (string $model) use ($filters, $branchApprovalScope) {
            $query = $model::query()
                ->with(['requester', 'currentApprover'])
                ->when($branchApprovalScope !== null, fn ($q) => $branchApprovalScope === [] ? $q->whereRaw('1 = 0') : $q->whereIn('branch_id', $branchApprovalScope));
            $this->applyChangeRequestFilters($query, $filters);
            return $query;
        };
        $deleteBase = $build(MonthlyPlanDeleteRequest::class);
        $editBase = $build(MonthlyPlanEditRequest::class);

        return [
            'delete' => ['total' => (clone $deleteBase)->count(), 'pending' => (clone $deleteBase)->where('status', 'pending')->count(), 'approved' => (clone $deleteBase)->where('status', 'approved')->count(), 'rejected' => (clone $deleteBase)->where('status', 'rejected')->count()],
            'edit' => ['total' => (clone $editBase)->count(), 'pending' => (clone $editBase)->where('status', 'pending')->count(), 'approved' => (clone $editBase)->where('status', 'approved')->count(), 'rejected' => (clone $editBase)->where('status', 'rejected')->count()],
            'by_branch' => (clone $deleteBase)->selectRaw('branch_id, count(*) as total')->groupBy('branch_id')->with('monthlyActivity.branch')->limit(8)->get(),
            'recent_delete' => (clone $deleteBase)->latest()->limit(5)->get(),
            'recent_edit' => (clone $editBase)->latest()->limit(5)->get(),
        ];
    }

    protected function applyChangeRequestFilters($query, array $filters): void
    {
        $query
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['approval_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($q, $dateFrom) => $q->whereDate('requested_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($q, $dateTo) => $q->whereDate('requested_at', '<=', $dateTo))
            ->when($filters['current_step'] ?? null, fn ($q, $step) => $q->whereHas('workflowInstance.currentStep', fn ($stepQuery) => $stepQuery->where('step_key', $step)));
    }

    protected function workflowTimelineForActivity(MonthlyActivity $activity): array
    {
        return $activity->workflowInstance?->logs?->map(fn ($log) => [
            'step_name' => $log->step?->name_ar ?? $log->step?->name_en ?? '-',
            'approver_name' => $log->actor?->name ?? '-',
            'status' => $log->action ?? $log->status ?? '-',
            'decided_at' => optional($log->created_at)->format('Y-m-d H:i') ?? '-',
            'comment' => $log->comment ?? null,
        ])->values()->all() ?? [];
    }

    protected function applyMonthlyActivityApprovalFilters($query, array $filters, string $dateColumn): void
    {
        $query
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['approval_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($q, $dateFrom) => $q->whereDate($dateColumn, '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($q, $dateTo) => $q->whereDate($dateColumn, '<=', $dateTo));
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
            'activity_date' => optional($activity->proposed_date)->format('Y-m-d') ?: optional($activity->activity_date)->format('Y-m-d'),
            'version_number' => (int) ($activity->version_number ?: $activity->plan_version ?: 1),
            'responsible_user' => $activity->responsible_party ?: ($workflowSummary['submitted_by_name'] ?? '-'),
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
            'activity_details_url' => route('role.programs.approvals.details', ['monthlyActivity' => $activity, 'view' => 'activity']),
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

    protected function applyWorkflowApprovalFilters(Builder $query, array $filters, User $viewer): void
    {
        if (! empty($filters['approval_status'])) {
            $status = (string) $filters['approval_status'];
            $query->where(function (Builder $statusQuery) use ($status): void {
                if ($status === 'approved') {
                    $statusQuery->whereHas('workflowInstance', fn (Builder $instance) => $instance->where('status', 'approved'));
                } elseif ($status === 'draft') {
                    $statusQuery->where('status', 'draft')
                        ->whereDoesntHave('workflowInstance', fn (Builder $instance) => $instance->where('status', 'approved'));
                } else {
                    $statusQuery->whereHas('workflowInstance', fn (Builder $instance) => $instance->whereNotIn('status', ['approved', 'rejected']))
                        ->orWhere(function (Builder $activity): void {
                            $activity->where('status', '!=', 'draft')->whereDoesntHave('workflowInstance');
                        });
                }
            });
        }

        if (! empty($filters['current_step'])) {
            $query->whereHas('workflowInstance.currentStep', fn (Builder $step) => $step->where('step_key', $filters['current_step']));
        }

        if (empty($filters['my_pending'])) {
            return;
        }

        $query->whereHas('workflowInstance', function (Builder $instance) use ($viewer): void {
            $instance->whereIn('status', ['pending', 'in_progress', 'changes_requested'])
                ->whereNotNull('current_step_id');

            if ($viewer->hasRole('super_admin')) {
                return;
            }

            $roleIds = $viewer->roles()->pluck('roles.id');
            $permissionIds = $viewer->getAllPermissions()
                ->filter(fn ($permission) => $viewer->can($permission->name))
                ->pluck('id');
            $instance->whereHas('currentStep', function (Builder $step) use ($roleIds, $permissionIds): void {
                $step->where(function (Builder $assignment) use ($roleIds, $permissionIds): void {
                    $assignment->whereIn('role_id', $roleIds)
                        ->orWhereIn('permission_id', $permissionIds);
                });
            });
        });
    }
}
