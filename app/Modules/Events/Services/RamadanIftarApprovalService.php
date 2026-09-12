<?php

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Models\WorkflowInstance;
use App\Modules\Events\Models\RamadanIftar;
use App\Services\DynamicWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarApprovalService
{
    private DynamicWorkflowService $workflows;

    public function __construct(DynamicWorkflowService $workflows)
    {
        $this->workflows = $workflows;
    }

    public function decide(RamadanIftar $iftar, User $actor, int $expectedStepId, string $decision, ?string $comment): array
    {
        if (! in_array($decision, [DynamicWorkflowService::DECISION_APPROVED, DynamicWorkflowService::DECISION_CHANGES_REQUESTED], true)) {
            throw ValidationException::withMessages(['decision' => 'The selected decision is invalid.']);
        }

        return DB::transaction(function () use ($iftar, $actor, $expectedStepId, $decision, $comment) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());
            if ($locked->status !== RamadanIftar::STATUS_SUBMITTED) {
                throw ValidationException::withMessages(['status' => 'This Ramadan Iftar is not pending approval.']);
            }

            $workflow = $this->workflows->findActiveWorkflow(RamadanIftar::WORKFLOW_MODULE);
            $instance = $workflow ? WorkflowInstance::query()
                ->where('workflow_id', $workflow->id)
                ->where('entity_type', RamadanIftar::class)
                ->where('entity_id', $locked->id)
                ->lockForUpdate()->first() : null;
            if (! $instance || ! $this->workflows->canDecide($instance)) {
                throw ValidationException::withMessages(['workflow' => 'No actionable Ramadan workflow was found.']);
            }

            $step = $this->workflows->currentStepForUser($instance, $actor);
            if (! $step || (int) $step->id !== $expectedStepId || (string) $step->step_type !== 'main') {
                abort(403);
            }
            abort_if((int) $locked->created_by === (int) $actor->id && ! $actor->hasRole('super_admin'), 403);
            $this->workflows->assertPrerequisites($instance, $step);
            $this->workflows->recordDecision($instance, $step, $actor, $decision, $comment);
            $instance->refresh();

            if ($decision === DynamicWorkflowService::DECISION_CHANGES_REQUESTED) {
                $locked->update(['status' => RamadanIftar::STATUS_CHANGES_REQUESTED, 'approved_at' => null]);
            } elseif ($instance->status === DynamicWorkflowService::DECISION_APPROVED) {
                $locked->update(['status' => RamadanIftar::STATUS_APPROVED, 'approved_at' => now()]);
            }

            WorkflowActionLog::query()->create([
                'module' => RamadanIftar::WORKFLOW_MODULE,
                'entity_type' => RamadanIftar::class,
                'entity_id' => $locked->id,
                'action_type' => 'approval_decision',
                'status' => $decision,
                'performed_by' => $actor->id,
                'notes' => $comment,
                'performed_at' => now(),
                'meta' => ['workflow_instance_id' => $instance->id, 'workflow_step_id' => $step->id],
            ]);

            return [$locked->fresh(), $instance->fresh()];
        });
    }
}
