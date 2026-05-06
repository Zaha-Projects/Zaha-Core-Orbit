<?php

namespace App\Services;

use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @deprecated This service only mirrors legacy status fields for backward compatibility.
 * Approval decisions must be executed through DynamicWorkflowService.
 */
class MonthlyActivityWorkflowService
{
    /**
     * @return array<int, array{key:string,label:string,status_field:?string,role:string}>
     */
    public function stepsFor(MonthlyActivity $activity): array
    {
        $steps = [];

        if ($activity->monthly_created_by_branch_relations) {
            $steps[] = [
                'key' => 'relations_officer_review',
                'label' => 'Branch Relations Officer',
                'status_field' => 'relations_officer_approval_status',
                'role' => 'relations_officer',
            ];

            $steps[] = [
                'key' => 'branch_relations_manager_review',
                'label' => 'Supervisor',
                'status_field' => 'relations_manager_approval_status',
                'role' => 'branch_relations_manager',
            ];

            if ($activity->monthly_branch_coordinator_required) {
                $steps[] = [
                    'key' => 'branch_coordinator_review',
                    'label' => 'Branch Coordinator',
                    'status_field' => 'liaison_approval_status',
                    'role' => 'branch_coordinator',
                ];
            }
        }

        if ($activity->monthly_created_by_primary_relations) {
            $steps[] = [
                'key' => 'primary_relations_officer_submit',
                'label' => 'Primary Relations Officer',
                'status_field' => 'relations_officer_approval_status',
                'role' => 'relations_officer',
            ];
        }

        $steps[] = [
            'key' => 'hq_relations_manager_review',
            'label' => 'HQ Relations Manager',
            'status_field' => 'hq_relations_manager_approval_status',
            'role' => 'relations_manager',
        ];

        if ((bool) $activity->executive_review_required) {
            $steps[] = [
                'key' => 'executive_review',
                'label' => 'Executive Manager',
                'status_field' => 'executive_approval_status',
                'role' => 'executive_manager',
            ];
        }

        return $steps;
    }

    public function currentStepForUser(MonthlyActivity $activity, User $user): ?array
    {
        return collect($this->stepsFor($activity))
            ->first(function (array $step) use ($user, $activity) {
                if (! $user->hasRole($step['role']) || ! $step['status_field']) {
                    return false;
                }

                $status = (string) $activity->{$step['status_field']};

                return in_array($status, ['pending', 'changes_requested'], true);
            });
    }

    public function assertPrerequisites(MonthlyActivity $activity, string $stepKey): void
    {
        $steps = collect($this->stepsFor($activity));
        $currentIndex = $steps->search(fn (array $step) => $step['key'] === $stepKey);

        abort_if($currentIndex === false, 422, __('app.roles.programs.monthly_activities.approvals.errors.prerequisite_missing'));

        $steps
            ->take($currentIndex)
            ->each(function (array $step) use ($activity) {
                if (! $step['status_field']) {
                    return;
                }

                abort_if(
                    $activity->{$step['status_field']} !== 'approved',
                    422,
                    __('app.roles.programs.monthly_activities.approvals.errors.prerequisite_missing')
                );
            });
    }

    public function buildStepLabelMap(MonthlyActivity $activity): array
    {
        return collect($this->stepsFor($activity))
            ->mapWithKeys(fn (array $step) => [$step['key'] => $step['label']])
            ->all();
    }

    public function initializeDynamicStatuses(MonthlyActivity $activity): void
    {
        $updates = [];

        foreach ($this->stepsFor($activity) as $step) {
            if (! $step['status_field']) {
                continue;
            }

            if (! array_key_exists($step['status_field'], $updates)) {
                $updates[$step['status_field']] = 'pending';
            }
        }

        $updates['programs_officer_approval_status'] = 'skipped';
        $updates['programs_manager_approval_status'] = 'skipped';
        $updates['executive_approval_status'] = $activity->executive_review_required ? 'pending' : 'skipped';

        $activity->update($updates);
    }

    public function resetDownstreamSteps(MonthlyActivity $activity, string $currentStepKey): array
    {
        $updates = [];
        $steps = collect($this->stepsFor($activity));
        $currentIndex = $steps->search(fn (array $step) => $step['key'] === $currentStepKey);

        if ($currentIndex === false) {
            return $updates;
        }

        $steps
            ->slice($currentIndex + 1)
            ->each(function (array $step) use (&$updates) {
                if ($step['status_field']) {
                    $updates[$step['status_field']] = 'pending';
                }
            });

        return $updates;
    }

    public function rollbackToBranchForChanges(MonthlyActivity $activity, string $currentStepKey): array
    {
        $updates = $this->resetDownstreamSteps($activity, $currentStepKey);
        $steps = collect($this->stepsFor($activity));
        $currentIndex = $steps->search(fn (array $step) => $step['key'] === $currentStepKey);

        if ($currentIndex === false) {
            return $updates;
        }

        $currentStep = $steps->get($currentIndex);

        if (($currentStep['status_field'] ?? null) === 'relations_officer_approval_status') {
            return $updates;
        }

        foreach (['relations_officer_approval_status', 'relations_manager_approval_status'] as $branchStatusField) {
            if (array_key_exists($branchStatusField, $updates) || ! in_array($branchStatusField, $activity->getFillable(), true)) {
                continue;
            }

            $updates[$branchStatusField] = 'pending';
        }

        return $updates;
    }

    public function serializeSteps(MonthlyActivity $activity): Collection
    {
        return collect($this->stepsFor($activity))
            ->map(function (array $step) use ($activity) {
                return [
                    'key' => $step['key'],
                    'label' => $step['label'],
                    'status' => $step['status_field'] ? $activity->{$step['status_field']} : 'n/a',
                ];
            });
    }
}
