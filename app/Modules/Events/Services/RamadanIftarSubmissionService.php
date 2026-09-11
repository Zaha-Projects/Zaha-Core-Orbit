<?php

namespace App\Modules\Events\Services;

use App\Models\ExecutionNeedType;
use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Modules\Events\Models\RamadanIftar;
use App\Services\DynamicWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarSubmissionService
{
    private DynamicWorkflowService $workflows;

    public function __construct(DynamicWorkflowService $workflows)
    {
        $this->workflows = $workflows;
    }

    public function submit(RamadanIftar $iftar, User $actor): RamadanIftar
    {
        return DB::transaction(function () use ($iftar, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());

            if (! $locked->isPlanningEditable()) {
                throw ValidationException::withMessages(['status' => 'This Ramadan Iftar cannot be submitted from its current status.']);
            }

            $this->assertReady($locked);
            $workflow = $this->workflows->findActiveWorkflow(RamadanIftar::WORKFLOW_MODULE);
            if (! $workflow) {
                throw ValidationException::withMessages(['workflow' => 'The Ramadan Iftar approval workflow is not configured.']);
            }

            $instance = WorkflowInstance::query()
                ->where('workflow_id', $workflow->id)
                ->where('entity_type', RamadanIftar::class)
                ->where('entity_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked->status === RamadanIftar::STATUS_CHANGES_REQUESTED) {
                if (! $instance) {
                    throw ValidationException::withMessages(['workflow' => 'The correction workflow instance is missing.']);
                }
                $this->workflows->markResubmitted($instance);
                $instance->refresh();
            } elseif ($instance) {
                throw ValidationException::withMessages(['workflow' => 'A workflow instance already exists for this Ramadan Iftar.']);
            } else {
                $instance = $this->workflows->forEntity($workflow, RamadanIftar::class, (int) $locked->getKey());
            }

            $step = $this->workflows->currentStep($instance);
            if (! $step || (string) $step->step_type !== 'sub') {
                throw ValidationException::withMessages(['workflow' => 'The Ramadan Iftar workflow is not ready for submission.']);
            }

            WorkflowLog::query()->create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'acted_by' => $actor->id,
                'action' => DynamicWorkflowService::DECISION_APPROVED,
                'comment' => $locked->status === RamadanIftar::STATUS_CHANGES_REQUESTED ? 'Ramadan Iftar corrections resubmitted.' : 'Ramadan Iftar submitted for approval.',
                'edit_request_iteration' => (int) $instance->edit_request_count,
                'acted_at' => now(),
            ]);
            $this->workflows->advanceToNextStep($instance->fresh());

            $locked->update([
                'status' => RamadanIftar::STATUS_SUBMITTED,
                'submitted_at' => $locked->submitted_at ?: now(),
            ]);

            WorkflowActionLog::query()->create([
                'module' => RamadanIftar::WORKFLOW_MODULE,
                'entity_type' => RamadanIftar::class,
                'entity_id' => $locked->id,
                'action_type' => 'submitted',
                'status' => RamadanIftar::STATUS_SUBMITTED,
                'performed_by' => $actor->id,
                'performed_at' => now(),
                'meta' => ['workflow_instance_id' => $instance->id, 'iteration' => (int) $instance->edit_request_count],
            ]);

            return $locked->fresh();
        });
    }

    private function assertReady(RamadanIftar $iftar): void
    {
        if (! $iftar->branch_id || ! $iftar->relations_officer_id || ! $iftar->planned_date) {
            throw ValidationException::withMessages(['planning' => 'Branch, relations officer, and planned date are required.']);
        }
        if (! $iftar->hasValidGuidanceAcceptance()) {
            throw ValidationException::withMessages(['guidance' => 'Valid Ramadan guidance acceptance is required.']);
        }

        $applicableIds = ExecutionNeedType::query()->canonical()->active()->forRamadanIftars()
            ->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $capturedIds = $iftar->executionNeeds()->whereHas('executionNeedType', function ($query) {
            $query->canonical()->active()->forRamadanIftars();
        })->orderBy('execution_need_type_id')->pluck('execution_need_type_id')->map(fn ($id) => (int) $id)->all();

        if ($applicableIds === [] || $capturedIds !== $applicableIds) {
            throw ValidationException::withMessages(['execution_needs' => 'Every applicable execution need requires an explicit planning decision.']);
        }
    }
}
