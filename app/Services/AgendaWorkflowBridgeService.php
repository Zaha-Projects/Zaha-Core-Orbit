<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Support\Carbon;

class AgendaWorkflowBridgeService
{
    public function __construct(
        protected DynamicWorkflowService $dynamicWorkflowService
    ) {
    }

    public function syncApprovalState(AgendaEvent $agendaEvent, ?WorkflowInstance $instance = null): AgendaEvent
    {
        $instance = $instance ?? $this->dynamicWorkflowService->forModel('agenda', $agendaEvent);

        if (! $instance) {
            return $agendaEvent;
        }

        $instance->loadMissing(
            'workflow.steps.role',
            'currentStep.role',
            'logs.step.role'
        );

        $currentStep = $this->dynamicWorkflowService->currentStep($instance);

        $agendaEvent->update([
            'status' => $this->deriveEventStatus($agendaEvent, $instance, $currentStep),
            'relations_approval_status' => $this->deriveLegacyRoleStatus($instance, 'relations_manager'),
            'executive_approval_status' => $this->deriveLegacyRoleStatus($instance, 'executive_manager'),
            'approved_by_relations_at' => $this->latestApprovedAtForRole($instance, 'relations_manager'),
            'approved_by_executive_at' => $this->latestApprovedAtForRole($instance, 'executive_manager'),
        ]);

        return $agendaEvent->fresh(['workflowInstance.currentStep.role', 'workflowInstance.logs.step.role', 'approvals.approver', 'creator']);
    }

    protected function deriveEventStatus(AgendaEvent $agendaEvent, WorkflowInstance $instance, ?WorkflowStep $currentStep): string
    {
        return match ((string) $instance->status) {
            DynamicWorkflowService::DECISION_APPROVED => 'published',
            DynamicWorkflowService::DECISION_REJECTED => 'rejected',
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED => 'changes_requested',
            default => $this->hasApprovedMainDecision($instance)
                ? 'relations_approved'
                : (in_array((string) $agendaEvent->status, ['draft', 'pending'], true) ? 'submitted' : ($agendaEvent->status ?: 'submitted')),
        };
    }

    protected function deriveLegacyRoleStatus(WorkflowInstance $instance, string $roleName): string
    {
        $stepsForRole = $instance->workflow?->steps
            ?->filter(fn (WorkflowStep $step): bool => (string) $step->role?->name === $roleName)
            ->pluck('id')
            ->all() ?? [];

        if ($stepsForRole === []) {
            return match ((string) $instance->status) {
                DynamicWorkflowService::DECISION_APPROVED => DynamicWorkflowService::DECISION_APPROVED,
                DynamicWorkflowService::DECISION_REJECTED => DynamicWorkflowService::DECISION_REJECTED,
                DynamicWorkflowService::DECISION_CHANGES_REQUESTED => DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
                default => 'pending',
            };
        }

        $latestRoleDecision = $instance->logs
            ->filter(fn (WorkflowLog $log): bool => in_array((int) $log->workflow_step_id, $stepsForRole, true))
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        if ($latestRoleDecision) {
            return (string) $latestRoleDecision->action;
        }

        return 'pending';
    }

    protected function latestApprovedAtForRole(WorkflowInstance $instance, string $roleName): ?Carbon
    {
        $approvedLog = $instance->logs
            ->filter(fn (WorkflowLog $log): bool =>
                (string) $log->step?->role?->name === $roleName
                && (string) $log->action === DynamicWorkflowService::DECISION_APPROVED
            )
            ->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)
            ->first();

        return $approvedLog?->acted_at;
    }

    protected function hasApprovedMainDecision(WorkflowInstance $instance): bool
    {
        return $instance->logs->contains(fn (WorkflowLog $log): bool =>
            (string) $log->action === DynamicWorkflowService::DECISION_APPROVED
            && (string) $log->step?->step_type !== 'sub'
        );
    }
}
