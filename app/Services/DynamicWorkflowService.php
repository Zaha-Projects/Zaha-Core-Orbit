<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;

class DynamicWorkflowService
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_CHANGES_REQUESTED = 'changes_requested';
    public const DECISION_REJECTED = 'rejected';

    public function findActiveWorkflow(string $module): ?Workflow
    {
        return Workflow::query()
            ->where('module', $module)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    public function forModel(string $module, Model $model): ?WorkflowInstance
    {
        $workflow = $this->findActiveWorkflow($module);

        if (! $workflow) {
            return null;
        }

        return $this->forEntity($workflow, get_class($model), (int) $model->getKey());
    }

    public function forEntity(Workflow $workflow, string $entityType, int $entityId): WorkflowInstance
    {
        return WorkflowInstance::query()->firstOrCreate(
            [
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'status' => 'pending',
                'started_at' => now(),
                'current_step_id' => optional($workflow->steps()->first())->id,
            ]
        );
    }

    public function currentStep(WorkflowInstance $instance): ?WorkflowStep
    {
        $instance->loadMissing('currentStep.role', 'currentStep.permission');

        return $instance->currentStep;
    }

    public function currentStepForUser(WorkflowInstance $instance, User $user): ?WorkflowStep
    {
        $step = $this->currentStep($instance);

        if (! $step) {
            return null;
        }

        $matchesRole = $step->role && $user->hasRole($step->role->name);

        return $matchesRole ? $step : null;
    }

    public function assertPrerequisites(WorkflowInstance $instance, WorkflowStep $step): void
    {
        $instance->loadMissing('workflow.steps');

        $requiredSteps = $instance->workflow->steps
            ->filter(fn (WorkflowStep $candidate) =>
                $candidate->step_order < $step->step_order
                || ($candidate->step_order === $step->step_order && $candidate->approval_level < $step->approval_level)
            );

        $approvedStepIds = $instance->logs()
            ->where('action', self::DECISION_APPROVED)
            ->pluck('workflow_step_id')
            ->filter()
            ->all();

        foreach ($requiredSteps as $requiredStep) {
            abort_if(! in_array($requiredStep->id, $approvedStepIds, true), 422, __('app.roles.programs.monthly_activities.approvals.errors.prerequisite_missing'));
        }
    }

    public function canDecide(WorkflowInstance $instance): bool
    {
        return in_array($instance->status, ['pending', 'in_progress', 'changes_requested'], true);
    }

    public function recordDecision(WorkflowInstance $instance, WorkflowStep $step, User $actor, string $decision, ?string $comment = null): WorkflowLog
    {
        abort_unless(in_array($decision, [self::DECISION_APPROVED, self::DECISION_CHANGES_REQUESTED, self::DECISION_REJECTED], true), 422, __('app.roles.programs.monthly_activities.approvals.errors.invalid_decision'));
        if (in_array($decision, [self::DECISION_CHANGES_REQUESTED, self::DECISION_REJECTED], true)) {
            abort_if(blank(trim((string) $comment)), 422, __('app.roles.programs.monthly_activities.approvals.errors.comment_required'));
        }

        $log = WorkflowLog::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'acted_by' => $actor->id,
            'action' => $decision,
            'comment' => $comment,
            'edit_request_iteration' => (int) $instance->edit_request_count,
            'acted_at' => now(),
        ]);

        if ($decision === self::DECISION_CHANGES_REQUESTED) {
            $instance->increment('edit_request_count');
            $this->rollbackForChanges($instance->fresh());

            return $log;
        }

        if ($decision === self::DECISION_REJECTED) {
            $instance->update([
                'status' => self::DECISION_REJECTED,
                'current_step_id' => null,
                'completed_at' => now(),
            ]);

            return $log;
        }

        $this->advanceToNextStep($instance->fresh());

        return $log;
    }

    public function markResubmitted(WorkflowInstance $instance): void
    {
        abort_unless($instance->status === self::DECISION_CHANGES_REQUESTED, 422, __('app.roles.programs.monthly_activities.approvals.errors.resubmit_only_after_changes_requested'));

        $instance->update([
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    public function rollbackForChanges(WorkflowInstance $instance): void
    {
        $instance->loadMissing('workflow.steps');

        $returnStep = $instance->workflow->steps
            ->first(fn (WorkflowStep $workflowStep) => (bool) $workflowStep->is_editable)
            ?? $instance->workflow->steps->first();

        $instance->update([
            'status' => self::DECISION_CHANGES_REQUESTED,
            'current_step_id' => $returnStep?->id,
            'completed_at' => null,
        ]);
    }

    public function advanceToNextStep(WorkflowInstance $instance): void
    {
        $instance->loadMissing('workflow.steps');

        $steps = $instance->workflow->steps->values();
        $currentIndex = $steps->search(fn (WorkflowStep $step) => $step->id === (int) $instance->current_step_id);

        if ($currentIndex === false) {
            $instance->update([
                'current_step_id' => optional($steps->first())->id,
                'status' => 'in_progress',
                'completed_at' => null,
            ]);

            return;
        }

        $nextStep = $steps->get($currentIndex + 1);

        if (! $nextStep) {
            $instance->update([
                'current_step_id' => null,
                'status' => self::DECISION_APPROVED,
                'completed_at' => now(),
            ]);

            return;
        }

        $instance->update([
            'current_step_id' => $nextStep->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    public function currentAssigneeLabel(WorkflowInstance $instance): string
    {
        $step = $this->currentStep($instance);

        if (! $step) {
            return __('app.common.na');
        }

        if ($step->role) {
            return $step->role->name;
        }

        return __('app.common.na');
    }
}
