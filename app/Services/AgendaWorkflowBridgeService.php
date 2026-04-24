<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;
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
        $oldStatus = (string) ($agendaEvent->status ?? '');
        $newStatus = $this->deriveEventStatus($agendaEvent, $instance, $currentStep);

        $agendaEvent->update([
            'status' => $newStatus,
            'relations_approval_status' => $this->deriveLegacyRoleStatus($instance, 'relations_manager'),
            'executive_approval_status' => $this->deriveLegacyRoleStatus($instance, 'executive_manager'),
            'approved_by_relations_at' => $this->latestApprovedAtForRole($instance, 'relations_manager'),
            'approved_by_executive_at' => $this->latestApprovedAtForRole($instance, 'executive_manager'),
        ]);

        $agendaEvent = $agendaEvent->fresh(['participations']);

        if ($oldStatus !== 'published' && $newStatus === 'published') {
            $this->syncUnifiedAgendaToMonthlyPlans($agendaEvent);
        }

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

    protected function syncUnifiedAgendaToMonthlyPlans(AgendaEvent $agendaEvent): void
    {
        if ((string) $agendaEvent->plan_type !== 'unified') {
            return;
        }

        $eventDate = optional($agendaEvent->event_date)?->toDateString();
        $resolvedDate = $eventDate
            ? Carbon::parse($eventDate)->toDateString()
            : Carbon::create(now()->year, (int) $agendaEvent->month, (int) $agendaEvent->day)->toDateString();

        $branchIds = [];
        if ((string) $agendaEvent->event_type === 'mandatory') {
            $branchIds = Branch::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $branchIds = $agendaEvent->participations()
                ->where('entity_type', 'branch')
                ->where('participation_status', 'participant')
                ->pluck('entity_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($branchIds as $branchId) {
            $monthlyActivity = MonthlyActivity::firstOrNew([
                'agenda_event_id' => $agendaEvent->id,
                'branch_id' => $branchId,
            ]);

            $monthlyActivity->fill([
                'month' => (int) Carbon::parse($resolvedDate)->format('m'),
                'day' => (int) Carbon::parse($resolvedDate)->format('d'),
                'title' => $agendaEvent->event_name,
                'proposed_date' => $resolvedDate,
                'is_in_agenda' => true,
                'is_from_agenda' => true,
                'participation_status' => 'participant',
                'plan_type' => $agendaEvent->plan_type ?? 'non_unified',
                'description' => $agendaEvent->notes,
                'location_type' => $monthlyActivity->location_type ?? 'inside_center',
                'status' => 'approved',
                'relations_manager_approval_status' => 'approved',
                'executive_approval_status' => 'approved',
                'lifecycle_status' => 'Approved',
                'created_by' => $monthlyActivity->created_by ?: $agendaEvent->created_by,
            ]);

            $monthlyActivity->save();
        }
    }
}
