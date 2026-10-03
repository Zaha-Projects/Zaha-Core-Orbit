<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns;

use App\Modules\Events\Models\MonthlyActivity;
use App\Models\User;

trait InteractsWithMonthlyActivityApprovals
{
    protected function abortIfProgramsManagerViewOnly(?User $user): void
    {
        abort_if($user?->hasRole('programs_manager') && ! $user?->hasRole('super_admin'), 403);
        abort_if(
            $user?->hasRole('communication_head')
            && ! $user?->hasAnyRole(['super_admin', 'relations_manager', 'relations_officer', 'supervisor', 'branch_coordinator', 'executive_manager']),
            403
        );
    }

    protected function canReviewPostExecution(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        if ($user === null || (string) $monthlyActivity->status !== 'post_execution_submitted') {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }
        if (! $user->hasRole('supervisor')) {
            return false;
        }
        $branchId = (int) $monthlyActivity->branch_id;
        return (int) ($user->branch_id ?? 0) === $branchId
            || $user->assignedBranches()->whereKey($branchId)->exists();
    }

    protected function executionNeedDecisionItemsForActivity(MonthlyActivity $activity, ?User $viewer): array
    {
        $definitions = $activity->enabledExecutionNeeds();
        $followups = collect($activity->execution_needs_followup ?? [])->keyBy(fn ($row) => (string) ($row['key'] ?? ''));
        return collect($definitions)->map(function (array $definition, string $key) use ($activity, $viewer, $followups) {
            $roles = (array) data_get(config('execution_needs.decision_matrix', []), $key.'.roles', []);
            if ($roles === []) { $roles = ['supervisor']; }
            $row = $followups->get($key, []);
            $status = (string) ($row['status'] ?? 'pending');
            if (in_array($status, ['secured', 'not_secured'], true)) { return null; }
            $canDecide = $viewer && ($viewer->hasRole('super_admin') || collect($roles)->contains(fn ($role) => $viewer->hasRole($role)));
            return [
                'key' => $key,
                'label' => $definition['label'] ?? $key,
                'description' => $definition['description'] ?? data_get($activity->execution_needs_payload, $key.'.notes', '-'),
                'status' => $status ?: 'pending',
                'approver' => collect($roles)->implode('، '),
                'requested_by' => $activity->creator?->name ?? '-',
                'can_decide' => (bool) $canDecide,
            ];
        })->filter()->values()->all();
    }

    protected function focusAreaLabels(): array
    {
        return (array) config('monthly_activity.decision_focus_areas', []);
    }

    protected function formatDecisionComment(?string $comment, array $focusAreas = []): ?string
    {
        $comment = trim((string) $comment);
        $labels = collect($focusAreas)
            ->map(fn ($area) => $this->focusAreaLabels()[$area] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return $comment !== '' ? $comment : null;
        }

        $prefix = 'الأقسام المحددة: '.$labels->implode('، ');

        return $comment !== '' ? $prefix."

السبب/التعديل المطلوب: ".$comment : $prefix;
    }

    protected function isMonthlyRelationsManagerFinalStep(string $stepKey): bool
    {
        return $stepKey === 'monthly_relations_manager_review';
    }

    /**
     * @return array<int, int>|null
     */
    protected function branchApprovalScope($user): ?array
    {
        if (! $user || $user->hasRole('super_admin') || $user->can('branches.view.all')) {
            return null;
        }

        if (! $user->hasAnyRole(['relations_officer', 'supervisor', 'branch_coordinator'])) {
            return null;
        }

        return method_exists($user, 'approvalBranchIds')
            ? $user->approvalBranchIds()
            : (filled($user->branch_id) ? [(int) $user->branch_id] : []);
    }

    /** Apply filters that depend on the workflow before pagination and its count query. */

}
