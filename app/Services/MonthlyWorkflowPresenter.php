<?php

namespace App\Services;

use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonthlyWorkflowPresenter
{
    public function __construct(
        protected DynamicWorkflowService $dynamicWorkflowService
    ) {
    }

    public function attach(MonthlyActivity $activity, ?User $viewer = null): MonthlyActivity
    {
        $activity->setAttribute('workflow_summary', $this->present($activity, $viewer));

        return $activity;
    }

    public function present(MonthlyActivity $activity, ?User $viewer = null): array
    {
        $instance = $activity->relationLoaded('workflowInstance')
            ? $activity->getRelation('workflowInstance')
            : $activity->workflowInstance;

        if (! $instance) {
            $instance = $this->dynamicWorkflowService->forModel('monthly_activities', $activity);
            $activity->setRelation('workflowInstance', $instance);
        }

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

        $workflow = $instance?->workflow ?? $this->dynamicWorkflowService->findActiveWorkflow('monthly_activities');
        $workflow?->loadMissing('steps.role', 'steps.permission');

        $steps = collect($workflow?->steps ?? [])->values();
        $currentStep = $instance ? $this->dynamicWorkflowService->currentStep($instance) : null;

        $logs = collect($instance?->logs ?? [])
            ->filter(fn (WorkflowLog $log): bool => $log->step !== null)
            ->sortBy(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->values();

        $latestDecisionByStepId = $logs
            ->groupBy('workflow_step_id')
            ->map(fn (Collection $group): ?WorkflowLog => $group->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)->first());

        $submitLog = $logs
            ->filter(fn (WorkflowLog $log): bool => (string) $log->step?->step_type === 'sub' && (string) $log->action === DynamicWorkflowService::DECISION_APPROVED)
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        $latestChangeRequest = $logs
            ->filter(fn (WorkflowLog $log): bool => (string) $log->action === DynamicWorkflowService::DECISION_CHANGES_REQUESTED)
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        $presentedSteps = $steps->map(function (WorkflowStep $step) use ($activity, $instance, $currentStep, $latestDecisionByStepId) {
            $applies = $this->stepAppliesToActivity($step, $activity);
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

        $businessStatus = $this->resolveBusinessStatus($activity, $instance, $submitLog);

        return [
            'status_key' => $businessStatus,
            'status_label' => $this->businessStatusLabel($businessStatus),
            'workflow_state' => (string) ($instance?->status ?? 'pending'),
            'workflow_state_label' => $this->workflowStateLabel((string) ($instance?->status ?? 'pending')),
            'current_step_key' => $currentStep?->step_key,
            'current_step_label' => $currentStep?->name_ar ?: ($currentStep?->name_en ?: __('workflow_ui.common.unknown_step')),
            'current_role_label' => $currentStep?->role?->display_name
                ?: ($currentStep?->permission?->name ? $this->fallbackLabel($currentStep->permission->name) : __('workflow_ui.common.none_option')),
            'completed_steps_count' => $presentedSteps->whereIn('state', ['submitted', DynamicWorkflowService::DECISION_APPROVED])->count(),
            'total_steps_count' => $presentedSteps->where('applies', true)->count(),
            'steps' => $presentedSteps->all(),
            'submitted_by_name' => $submitLog?->actor?->name,
            'submitted_at' => $submitLog?->acted_at?->format('Y-m-d H:i'),
            'latest_change_request' => $latestChangeRequest ? [
                'step_label' => $latestChangeRequest->step?->name_ar ?: ($latestChangeRequest->step?->name_en ?: __('workflow_ui.common.unknown_step')),
                'role_label' => $latestChangeRequest->step?->role?->display_name
                    ?: ($latestChangeRequest->step?->permission?->name ? $this->fallbackLabel($latestChangeRequest->step->permission->name) : __('workflow_ui.common.none_option')),
                'actor_name' => $latestChangeRequest->actor?->name ?? __('app.common.na'),
                'comment' => $latestChangeRequest->comment,
                'acted_at' => $latestChangeRequest->acted_at?->format('Y-m-d H:i'),
            ] : null,
            'timeline' => $logs->map(function (WorkflowLog $log) {
                $action = (string) $log->action;
                if ((string) $log->step?->step_type === 'sub' && $action === DynamicWorkflowService::DECISION_APPROVED) {
                    $action = 'submitted';
                }

                return [
                    'step_key' => $log->step?->step_key,
                    'step_label' => $log->step?->name_ar ?: ($log->step?->name_en ?: __('workflow_ui.common.unknown_step')),
                    'role_label' => $log->step?->role?->display_name
                        ?: ($log->step?->permission?->name ? $this->fallbackLabel($log->step->permission->name) : __('workflow_ui.common.none_option')),
                    'actor_name' => $log->actor?->name ?? __('app.common.na'),
                    'action' => $action,
                    'action_label' => $this->stepStateLabel($action),
                    'comment' => $log->comment,
                    'acted_at' => $log->acted_at?->format('Y-m-d H:i'),
                ];
            })->all(),
            'can_current_user_decide' => $viewer && $instance
                ? ($this->dynamicWorkflowService->canDecide($instance) && $this->dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null)
                : false,
        ];
    }

    protected function resolveBusinessStatus(MonthlyActivity $activity, ?WorkflowInstance $instance, ?WorkflowLog $submitLog): string
    {
        if (! $instance) {
            return (string) ($activity->status ?: 'draft');
        }

        return match ((string) $instance->status) {
            DynamicWorkflowService::DECISION_APPROVED => 'approved',
            DynamicWorkflowService::DECISION_REJECTED => 'rejected',
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED => 'changes_requested',
            default => $submitLog ? ((string) ($activity->status ?: 'submitted')) : 'draft',
        };
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

    protected function stepAppliesToActivity(WorkflowStep $step, MonthlyActivity $activity): bool
    {
        if (! filled($step->condition_field)) {
            return true;
        }

        return $this->normalizeComparableValue(data_get($activity, $step->condition_field))
            === $this->normalizeComparableValue($step->condition_value);
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

    protected function businessStatusLabel(string $status): string
    {
        foreach ([
            'app.roles.programs.monthly_activities.statuses.' . $status,
            'workflow_ui.approvals.status_labels.' . $status,
        ] as $translationKey) {
            $translated = __($translationKey);

            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        return $this->fallbackLabel($status);
    }

    protected function workflowStateLabel(string $state): string
    {
        $translationKey = 'workflow_ui.approvals.status_labels.' . $state;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        if (app()->getLocale() === 'ar') {
            return match ($state) {
                'submitted' => 'مرسل',
                'draft' => 'مسودة',
                'current' => 'عند هذه الخطوة',
                'skipped' => 'غير مطلوب',
                'awaiting_resubmission' => 'بانتظار إعادة الإرسال',
                default => $this->fallbackLabel($state),
            };
        }

        return $this->fallbackLabel($state);
    }

    protected function stepStateLabel(string $state): string
    {
        $translationKey = 'workflow_ui.approvals.status_labels.' . $state;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return $this->fallbackLabel($state);
    }

    protected function fallbackLabel(?string $value): string
    {
        if (! $value) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }
}
