<?php

namespace App\Services;

use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        $instance->loadMissing('workflow.steps.role', 'workflow.steps.permission', 'currentStep.role', 'currentStep.permission');

        return $this->normalizeCurrentStep($instance);
    }

    public function currentStepForUser(WorkflowInstance $instance, User $user): ?WorkflowStep
    {
        $step = $this->currentStep($instance);

        if (! $step) {
            return null;
        }

        if ($user->hasRole('super_admin')) {
            return $step;
        }

        $instance->loadMissing('workflow', 'currentStep.role', 'currentStep.permission');

        $matchesRole = false;
        if ($step->role) {
            $allowedRoles = collect([$step->role->name])
                ->merge($this->equivalentRolesForStep($instance, $step))
                ->filter()
                ->unique();

            $matchesRole = $allowedRoles->contains(fn (string $roleName): bool => $user->hasRole($roleName))
                && $this->matchesStepAssignmentScope($instance, $step, $user);
        }

        $matchesPermission = $step->permission && $user->can($step->permission->name);

        return ($matchesRole || $matchesPermission) ? $step : null;
    }

    public function assertPrerequisites(WorkflowInstance $instance, WorkflowStep $step): void
    {
        $instance->loadMissing('workflow.steps');
        $entity = $this->resolveEntity($instance);

        $requiredSteps = $instance->workflow->steps
            ->filter(fn (WorkflowStep $candidate) => $this->stepAppliesToEntity($candidate, $entity))
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
        $entity = $this->resolveEntity($instance);

        $returnStep = $instance->workflow->steps
            ->first(fn (WorkflowStep $workflowStep) => (bool) $workflowStep->is_editable && $this->stepAppliesToEntity($workflowStep, $entity))
            ?? $this->firstApplicableStep($instance->workflow->steps, $entity);

        $instance->update([
            'status' => self::DECISION_CHANGES_REQUESTED,
            'current_step_id' => $returnStep?->id,
            'completed_at' => null,
        ]);
    }

    public function advanceToNextStep(WorkflowInstance $instance): void
    {
        $instance->loadMissing('workflow.steps.role', 'workflow.steps.permission');
        $entity = $this->resolveEntity($instance);

        $steps = $instance->workflow->steps->values();
        $currentIndex = $steps->search(fn (WorkflowStep $step) => $step->id === (int) $instance->current_step_id);

        if ($currentIndex === false) {
            $this->updateInstanceToStep(
                $instance,
                $this->firstApplicableStep($steps, $entity)
            );

            return;
        }

        $nextStep = $this->firstApplicableStep($steps->slice($currentIndex + 1), $entity);

        $this->updateInstanceToStep($instance, $nextStep);
    }

    public function currentAssigneeLabel(WorkflowInstance $instance): string
    {
        $step = $this->currentStep($instance);

        if (! $step) {
            return __('app.common.na');
        }

        if ($step->role) {
            return $step->role->display_name ?? $step->role->name;
        }

        return __('app.common.na');
    }

    public function eligibleUsersForStep(WorkflowInstance $instance, ?WorkflowStep $step = null): Collection
    {
        $step = $step ?? $this->currentStep($instance);

        if (! $step?->role) {
            return collect();
        }

        $roleNames = collect([$step->role->name])
            ->merge($this->equivalentRolesForStep($instance, $step))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query = User::query()->role($roleNames);

        if ($this->isBranchScopedStep($instance, $step)) {
            $branchId = $this->resolveBranchId($instance);
            if ($branchId) {
                $this->applyBranchScopedApproverFilter($query, $step, $branchId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->get();
    }

    /**
     * @return array<int, string>
     */
    private function equivalentRolesForStep(WorkflowInstance $instance, WorkflowStep $step): array
    {
        return [];
    }

    private function normalizeCurrentStep(WorkflowInstance $instance): ?WorkflowStep
    {
        $steps = $instance->workflow?->steps?->values() ?? collect();
        $entity = $this->resolveEntity($instance);

        if ($steps->isEmpty()) {
            return null;
        }

        if (in_array($instance->status, [self::DECISION_APPROVED, self::DECISION_REJECTED], true) && empty($instance->current_step_id)) {
            return null;
        }

        $currentStep = $steps->firstWhere('id', (int) $instance->current_step_id);

        if (! $currentStep) {
            $firstStep = $this->firstApplicableStep($steps, $entity);
            $this->updateInstanceToStep($instance, $firstStep);

            return $firstStep;
        }

        if ($this->stepAppliesToEntity($currentStep, $entity)) {
            return $currentStep;
        }

        $currentIndex = $steps->search(fn (WorkflowStep $step) => $step->id === $currentStep->id);
        $nextStep = $this->firstApplicableStep($steps->slice($currentIndex + 1), $entity);
        $this->updateInstanceToStep($instance, $nextStep);

        return $nextStep;
    }

    private function updateInstanceToStep(WorkflowInstance $instance, ?WorkflowStep $step): void
    {
        if (! $step) {
            $instance->update([
                'current_step_id' => null,
                'status' => self::DECISION_APPROVED,
                'completed_at' => now(),
            ]);

            return;
        }

        $instance->update([
            'current_step_id' => $step->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    private function firstApplicableStep(Collection $steps, ?Model $entity): ?WorkflowStep
    {
        return $steps->first(fn (WorkflowStep $step) => $this->stepAppliesToEntity($step, $entity));
    }

    private function stepAppliesToEntity(WorkflowStep $step, ?Model $entity): bool
    {
        if (! $step->hasCondition()) {
            return true;
        }

        if (! $entity) {
            return false;
        }

        $actualValue = data_get($entity, $step->condition_field);

        return $this->normalizeComparableValue($actualValue) === $this->normalizeComparableValue($step->condition_value);
    }

    private function normalizeComparableValue(mixed $value): string
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

    private function matchesStepAssignmentScope(WorkflowInstance $instance, WorkflowStep $step, User $user): bool
    {
        if (! $this->isBranchScopedStep($instance, $step)) {
            return true;
        }

        $branchId = $this->resolveBranchId($instance);

        if ($branchId === null) {
            return false;
        }

        if ((string) $step->role?->name === 'branch_coordinator') {
            return $user->isAssignedToApprovalBranch($branchId);
        }

        return (int) $user->branch_id === $branchId;
    }

    private function isBranchScopedStep(WorkflowInstance $instance, WorkflowStep $step): bool
    {
        if (($instance->workflow?->module ?? null) !== 'monthly_activities') {
            return false;
        }

        return in_array((string) $step->role?->name, [
            'branch_relations_officer',
            'branch_relations_manager',
            'branch_coordinator',
        ], true);
    }

    private function resolveBranchId(WorkflowInstance $instance): ?int
    {
        $entity = $this->resolveEntity($instance);

        if (! $entity instanceof MonthlyActivity || empty($entity->branch_id)) {
            return null;
        }

        return (int) $entity->branch_id;
    }

    private function resolveEntity(WorkflowInstance $instance): ?Model
    {
        $entityType = $instance->entity_type;

        if (! is_string($entityType) || ! class_exists($entityType) || ! is_subclass_of($entityType, Model::class)) {
            return null;
        }

        return $entityType::query()->find($instance->entity_id);
    }

    private function applyBranchScopedApproverFilter($query, WorkflowStep $step, int $branchId): void
    {
        if ((string) $step->role?->name !== 'branch_coordinator') {
            $query->where('branch_id', $branchId);

            return;
        }

        $query->where(function ($branchQuery) use ($branchId) {
            $branchQuery->whereHas('assignedBranches', function ($assignedQuery) use ($branchId) {
                $assignedQuery->where('branches.id', $branchId);
            })->orWhere(function ($fallbackQuery) use ($branchId) {
                $fallbackQuery
                    ->whereDoesntHave('assignedBranches')
                    ->where('branch_id', $branchId);
            });
        });
    }
}
