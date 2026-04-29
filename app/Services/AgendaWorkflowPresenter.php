<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\EventStatusLookup;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AgendaWorkflowPresenter
{
    public function __construct(
        protected DynamicWorkflowService $dynamicWorkflowService
    ) {
    }

    public function attach(AgendaEvent $agendaEvent, ?User $viewer = null): AgendaEvent
    {
        $agendaEvent->setAttribute('workflow_summary', $this->present($agendaEvent, $viewer));

        return $agendaEvent;
    }

    public function present(AgendaEvent $agendaEvent, ?User $viewer = null): array
    {
        $instance = $agendaEvent->relationLoaded('workflowInstance')
            ? $agendaEvent->getRelation('workflowInstance')
            : $agendaEvent->workflowInstance;

        if ($instance) {
            $instance->loadMissing(
                'workflow.steps.role',
                'workflow.steps.permission',
                'currentStep.role',
                'currentStep.permission',
                'logs.step.role',
                'logs.step.permission',
                'logs.actor'
            );
        }

        $workflow = $instance?->workflow ?? $this->dynamicWorkflowService->findActiveWorkflow('agenda');
        $workflow?->loadMissing('steps.role', 'steps.permission');

        $steps = collect($workflow?->steps ?? [])->values();

        $logs = collect($instance?->logs ?? [])
            ->filter(fn (WorkflowLog $log): bool => $log->step !== null)
            ->sortBy(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->values();

        $latestDecisionByStepId = $logs
            ->groupBy('workflow_step_id')
            ->map(fn (Collection $group): ?WorkflowLog => $group->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)->first());

        $currentStep = $instance ? $this->dynamicWorkflowService->currentStep($instance) : $steps->first();
        $businessStatus = (string) ($agendaEvent->status ?: ($instance?->status ?? 'draft'));
        $workflowState = (string) ($instance?->status ?? ($businessStatus === 'draft' ? 'pending' : 'in_progress'));

        $submitLog = $logs
            ->filter(fn (WorkflowLog $log): bool => (string) $log->step?->step_type === 'sub' && (string) $log->action === DynamicWorkflowService::DECISION_APPROVED)
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        $latestChangeRequest = $logs
            ->filter(fn (WorkflowLog $log): bool => (string) $log->action === DynamicWorkflowService::DECISION_CHANGES_REQUESTED)
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();
        $latestMainApproval = $logs
            ->filter(fn (WorkflowLog $log): bool =>
                (string) $log->step?->step_type !== 'sub'
                && (string) $log->action === DynamicWorkflowService::DECISION_APPROVED
            )
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        $presentedSteps = $steps->map(function (WorkflowStep $step) use ($agendaEvent, $instance, $latestDecisionByStepId, $currentStep) {
            $applies = $this->stepAppliesToAgendaEvent($step, $agendaEvent);
            $latestDecision = $latestDecisionByStepId->get($step->id);
            $state = $this->resolveStepState($step, $instance, $currentStep, $latestDecision, $applies);

            return [
                'key' => $step->step_key,
                'label' => $step->name_ar ?: ($step->name_en ?: $this->fallbackLabel($step->step_key)),
                'role_label' => $step->role?->display_name
                    ?: ($step->permission?->name ? $this->fallbackLabel($step->permission->name) : ($step->role?->name ? $this->fallbackLabel($step->role->name) : __('app.common.na'))),
                'step_type' => $step->step_type,
                'applies' => $applies,
                'state' => $state,
                'state_label' => $this->stepStateLabel($state),
                'acted_at' => $latestDecision?->acted_at?->format('Y-m-d H:i'),
                'actor_name' => $latestDecision?->actor?->name,
                'comment' => $latestDecision?->comment,
                'is_current' => (int) ($currentStep?->id ?? 0) === (int) $step->id,
            ];
        })->values();

        $approvedSteps = $presentedSteps->whereIn('state', ['submitted', DynamicWorkflowService::DECISION_APPROVED])->count();
        $mainApprovedStepsCount = $logs->filter(fn (WorkflowLog $log): bool =>
            (string) $log->step?->step_type !== 'sub'
            && (string) $log->action === DynamicWorkflowService::DECISION_APPROVED
        )->count();
        $approvalFilter = $this->resolveApprovalFilter($businessStatus, $instance, $currentStep, $mainApprovedStepsCount);

        return [
            'status_key' => $businessStatus,
            'status_label' => $this->agendaStatusLabel($businessStatus),
            'approval_filter_key' => $approvalFilter['key'],
            'approval_filter_label' => $approvalFilter['label'],
            'workflow_state' => $workflowState,
            'workflow_state_label' => $this->workflowStateLabel($workflowState),
            'current_step_key' => $currentStep?->step_key,
            'current_step_label' => $currentStep?->name_ar ?: ($currentStep?->name_en ?: __('app.common.na')),
            'current_role_label' => $currentStep?->role?->display_name
                ?: ($currentStep?->permission?->name ? $this->fallbackLabel($currentStep->permission->name) : ($currentStep?->role?->name ? $this->fallbackLabel($currentStep->role->name) : __('app.common.na'))),
            'completed_steps_count' => $approvedSteps,
            'total_steps_count' => $presentedSteps->where('applies', true)->count(),
            'submitted_by_name' => $submitLog?->actor?->name,
            'submitted_at' => $submitLog?->acted_at?->format('Y-m-d H:i'),
            'latest_approval_actor_name' => $latestMainApproval?->actor?->name,
            'latest_approval_role_label' => $latestMainApproval?->step?->role?->display_name
                ?: ($latestMainApproval?->step?->permission?->name ? $this->fallbackLabel($latestMainApproval->step->permission->name) : ($latestMainApproval?->step?->role?->name ? $this->fallbackLabel($latestMainApproval->step->role->name) : null)),
            'latest_approval_at' => $latestMainApproval?->acted_at?->format('Y-m-d H:i'),
            'latest_change_request' => $latestChangeRequest ? [
                'step_label' => $latestChangeRequest->step?->name_ar ?: ($latestChangeRequest->step?->name_en ?: __('app.common.na')),
                'role_label' => $latestChangeRequest->step?->role?->display_name
                    ?: ($latestChangeRequest->step?->permission?->name ? $this->fallbackLabel($latestChangeRequest->step->permission->name) : ($latestChangeRequest->step?->role?->name ? $this->fallbackLabel($latestChangeRequest->step->role->name) : __('app.common.na'))),
                'actor_name' => $latestChangeRequest->actor?->name ?? __('app.common.na'),
                'comment' => $latestChangeRequest->comment,
                'acted_at' => $latestChangeRequest->acted_at?->format('Y-m-d H:i'),
            ] : null,
            'can_current_user_decide' => $viewer && $instance
                ? ($this->dynamicWorkflowService->canDecide($instance) && $this->dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null)
                : false,
            'steps' => $presentedSteps->all(),
            'timeline' => $logs->map(function (WorkflowLog $log) {
                $action = (string) $log->action;

                if ((string) $log->step?->step_type === 'sub' && $action === DynamicWorkflowService::DECISION_APPROVED) {
                    $action = 'submitted';
                }

                return [
                    'step_key' => $log->step?->step_key,
                    'step_label' => $log->step?->name_ar ?: ($log->step?->name_en ?: __('app.common.na')),
                    'role_label' => $log->step?->role?->display_name
                        ?: ($log->step?->permission?->name ? $this->fallbackLabel($log->step->permission->name) : ($log->step?->role?->name ? $this->fallbackLabel($log->step->role->name) : __('app.common.na'))),
                    'actor_name' => $log->actor?->name ?? __('app.common.na'),
                    'action' => $action,
                    'action_label' => $this->stepStateLabel($action),
                    'comment' => $log->comment,
                    'acted_at' => $log->acted_at?->format('Y-m-d H:i'),
                ];
            })->all(),
        ];
    }

    protected function resolveStepState(
        WorkflowStep $step,
        ?WorkflowInstance $instance,
        ?WorkflowStep $currentStep,
        ?WorkflowLog $latestDecision,
        bool $applies
    ): string {
        if (! $applies) {
            return 'skipped';
        }

        if (
            (int) ($currentStep?->id ?? 0) === (int) $step->id
            && (string) $step->step_type === 'sub'
            && (string) ($instance?->status ?? '') === DynamicWorkflowService::DECISION_CHANGES_REQUESTED
        ) {
            return 'awaiting_resubmission';
        }

        if ($latestDecision) {
            if ((string) $step->step_type === 'sub' && (string) $latestDecision->action === DynamicWorkflowService::DECISION_APPROVED) {
                return 'submitted';
            }

            return (string) $latestDecision->action;
        }

        if ((int) ($currentStep?->id ?? 0) === (int) $step->id) {
            if ((string) $step->step_type === 'sub') {
                return (string) ($instance?->status) === DynamicWorkflowService::DECISION_CHANGES_REQUESTED
                    ? 'awaiting_resubmission'
                    : 'draft';
            }

            return 'current';
        }

        return 'pending';
    }

    protected function resolveApprovalFilter(
        string $businessStatus,
        ?WorkflowInstance $instance,
        ?WorkflowStep $currentStep,
        int $mainApprovedStepsCount
    ): array {
        if ($businessStatus === 'draft') {
            return [
                'key' => 'draft',
                'label' => $this->agendaStatusLabel('draft'),
            ];
        }

        if ($businessStatus === 'published') {
            return [
                'key' => 'published',
                'label' => $this->agendaStatusLabel('published'),
            ];
        }

        $isWaitingApproval = $currentStep !== null
            && (string) $currentStep->step_type !== 'sub'
            && ! in_array((string) ($instance?->status ?? ''), [
                DynamicWorkflowService::DECISION_APPROVED,
                DynamicWorkflowService::DECISION_REJECTED,
                DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
            ], true);

        if ($isWaitingApproval && $mainApprovedStepsCount > 0) {
            $roleLabel = $currentStep->role?->display_name
                ?: ($currentStep->permission?->name ? $this->fallbackLabel($currentStep->permission->name) : ($currentStep->role?->name ? $this->fallbackLabel($currentStep->role->name) : __('app.common.na')));

            return [
                'key' => 'pending_approval:' . ((string) $currentStep->id),
                'label' => __('app.roles.relations.approvals.filters.pending_role', ['role' => $roleLabel]),
            ];
        }

        if ($businessStatus === 'submitted' || $isWaitingApproval) {
            return [
                'key' => 'submitted',
                'label' => $this->agendaStatusLabel('submitted'),
            ];
        }

        return [
            'key' => $businessStatus,
            'label' => $this->agendaStatusLabel($businessStatus),
        ];
    }

    protected function stepAppliesToAgendaEvent(WorkflowStep $step, AgendaEvent $agendaEvent): bool
    {
        if (! filled($step->condition_field)) {
            return true;
        }

        $actualValue = data_get($agendaEvent, $step->condition_field);

        return $this->normalizeComparableValue($actualValue) === $this->normalizeComparableValue($step->condition_value);
    }

    protected function normalizeComparableValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'true', 'yes', 'on' => '1',
            'false', 'no', 'off', '' => '0',
            default => $normalized,
        };
    }

    protected function agendaStatusLabel(string $status): string
    {
        return EventStatusLookup::labelFor('agenda', $status)
            ?: $this->fallbackLabel($status);
    }

    protected function workflowStateLabel(string $state): string
    {
        foreach ([
            'workflow_ui.approvals.status_labels.' . $state,
            'app.roles.relations.agenda.status_labels.' . $state,
        ] as $translationKey) {
            $translated = __($translationKey);

            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        return $this->fallbackLabel($state);
    }

    protected function stepStateLabel(string $state): string
    {
        $translated = __('workflow_ui.approvals.status_labels.' . $state);

        if ($translated !== 'workflow_ui.approvals.status_labels.' . $state) {
            return $translated;
        }

        if (app()->getLocale() === 'ar') {
            return match ($state) {
                'submitted' => 'مرسل',
                'draft' => 'مسودة',
                'current' => 'عند هذه الخطوة',
                'skipped' => 'غير مطلوب',
                'awaiting_resubmission' => 'بانتظار إعادة الإرسال',
                default => $this->workflowStateLabel($state),
            };
        }

        return $this->workflowStateLabel($state);
    }

    protected function fallbackLabel(?string $value): string
    {
        if (! $value) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }
}
