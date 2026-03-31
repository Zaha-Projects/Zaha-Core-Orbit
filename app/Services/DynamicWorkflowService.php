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
        $matchesPermission = $step->permission && $user->can($step->permission->name);

        return ($matchesRole || $matchesPermission) ? $step : null;
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
            ->where('action', 'approved')
            ->pluck('workflow_step_id')
            ->filter()
            ->all();

        foreach ($requiredSteps as $requiredStep) {
            abort_if(! in_array($requiredStep->id, $approvedStepIds, true), 422, __('app.roles.programs.monthly_activities.approvals.errors.prerequisite_missing'));
        }
    }

    public function recordDecision(WorkflowInstance $instance, WorkflowStep $step, User $actor, string $decision, ?string $comment = null): WorkflowLog
    {
        $log = WorkflowLog::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'acted_by' => $actor->id,
            'action' => $decision,
            'comment' => $comment,
            'edit_request_iteration' => (int) $instance->edit_request_count,
            'acted_at' => now(),
        ]);

        if ($decision === 'changes_requested') {
            $instance->increment('edit_request_count');
            $instance->update(['status' => 'changes_requested']);

            return $log;
        }

        if ($decision === 'approved') {
            $this->advanceToNextStep($instance->fresh());
        }

        return $log;
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
            ]);

            return;
        }

        $nextStep = $steps->get($currentIndex + 1);

        if (! $nextStep) {
            $instance->update([
                'current_step_id' => null,
                'status' => 'approved',
                'completed_at' => now(),
            ]);

            return;
        }

        $instance->update([
            'current_step_id' => $nextStep->id,
            'status' => 'in_progress',
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

        if ($step->permission) {
            return $step->permission->name;
        }

        return __('app.common.na');
    }
}
