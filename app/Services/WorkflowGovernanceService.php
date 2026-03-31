<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\DB;

class WorkflowGovernanceService
{
    public function createWorkflow(array $data): Workflow
    {
        return DB::transaction(function () use ($data) {
            if ((bool) ($data['is_active'] ?? true)) {
                $this->deactivateOthers($data['module']);
            }

            return Workflow::query()->create([
                'module' => $data['module'],
                'code' => $data['code'],
                'name_ar' => $data['name_ar'] ?? null,
                'name_en' => $data['name_en'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        });
    }

    public function updateWorkflow(Workflow $workflow, array $data): void
    {
        DB::transaction(function () use ($workflow, $data) {
            if ((bool) ($data['is_active'] ?? false)) {
                $this->deactivateOthers($data['module'], $workflow->id);
            }

            $workflow->update([
                'module' => $data['module'],
                'code' => $data['code'],
                'name_ar' => $data['name_ar'] ?? null,
                'name_en' => $data['name_en'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);
        });
    }

    public function validateStepUniqueness(Workflow $workflow, array $data, ?int $ignoreStepId = null): void
    {
        $stepKeyExists = WorkflowStep::query()
            ->where('workflow_id', $workflow->id)
            ->where('step_key', $data['step_key'])
            ->when($ignoreStepId, fn ($q) => $q->whereKeyNot($ignoreStepId))
            ->exists();

        abort_if($stepKeyExists, 422, __('app.roles.super_admin.workflows.errors.step_key_duplicate'));

        $orderLevelExists = WorkflowStep::query()
            ->where('workflow_id', $workflow->id)
            ->where('step_order', $data['step_order'])
            ->where('approval_level', $data['approval_level'])
            ->when($ignoreStepId, fn ($q) => $q->whereKeyNot($ignoreStepId))
            ->exists();

        abort_if($orderLevelExists, 422, __('app.roles.super_admin.workflows.errors.step_order_level_duplicate'));
    }

    private function deactivateOthers(string $module, ?int $exceptWorkflowId = null): void
    {
        Workflow::query()
            ->where('module', $module)
            ->when($exceptWorkflowId, fn ($q) => $q->whereKeyNot($exceptWorkflowId))
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
