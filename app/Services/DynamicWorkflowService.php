<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;

class DynamicWorkflowService
{
    public function findActiveWorkflow(string $code): ?Workflow
    {
        return Workflow::query()->where('code', $code)->where('is_active', true)->first();
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

    public function currentStepForUser(WorkflowInstance $instance, User $user): ?WorkflowStep
    {
        $instance->loadMissing('workflow.steps.role', 'workflow.steps.permission');

        return $instance->workflow->steps
            ->first(function (WorkflowStep $step) use ($instance, $user) {
                if ($instance->current_step_id && $step->id !== (int) $instance->current_step_id) {
                    return false;
                }

                $matchesRole = $step->role && $user->hasRole($step->role->name);
                $matchesPermission = $step->permission && $user->can($step->permission->name);

                return $matchesRole || $matchesPermission;
            });
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
}
