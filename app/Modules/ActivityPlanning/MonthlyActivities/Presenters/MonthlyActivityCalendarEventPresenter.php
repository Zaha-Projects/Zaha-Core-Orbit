<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Presenters;

use App\Models\MonthlyActivity;
use App\Models\User;

final class MonthlyActivityCalendarEventPresenter
{
    private const EDIT_ROLES = [
        'relations_manager',
        'relations_officer',
        'supervisor',
        'relations_officer',
        'followup_officer',
        'evaluation_officer',
        'volunteer_coordinator',
        'branch_coordinator',
        'communication_head',
        'transport_officer',
        'movement_manager',
        'administrative_unit_manager',
        'super_admin',
    ];

    public function present(MonthlyActivity $activity, int $year, ?User $user, string $viewScope): array
    {
        $isReadOnlyUnified = $this->isReadOnlyUnifiedAgendaActivity($activity);
        $canBranchPartialEditUnified = $this->canBranchEditUnifiedNonCoreFields($activity, $user);
        $canCompleteAfterExecution = $this->canCompleteAfterExecution($activity, $user);
        $canOpenEdit = $this->canUseEditRoute($user);

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'date' => optional($activity->proposed_date)->format('Y-m-d')
                ?? sprintf('%04d-%02d-%02d', $year, $activity->month, $activity->day),
            'branch' => $activity->branch?->name,
            'status' => $activity->status,
            'source_label' => $activity->is_in_agenda
                ? __('app.roles.programs.monthly_activities.sources.from_agenda')
                : __('app.roles.programs.monthly_activities.sources.manual'),
            'event_type' => $activity->agendaEvent?->event_type,
            'event_type_label' => $activity->agendaEvent?->event_type
                ? __('app.roles.relations.agenda.types.' . $activity->agendaEvent->event_type)
                : null,
            'plan_type' => $activity->plan_type,
            'plan_type_label' => $activity->plan_type
                ? __('app.roles.relations.agenda.plans.' . $activity->plan_type)
                : null,
            'plan_version' => (int) ($activity->plan_version ?: 1),
            'requires_workshops' => (bool) $activity->requires_workshops,
            'requires_communications' => (bool) $activity->requires_communications,
            'edit_url' => route('role.relations.activities.edit', $activity),
            'post_execution_url' => $canCompleteAfterExecution
                ? route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post'])
                : null,
            'can_complete_after_execution' => $canCompleteAfterExecution,
            'open_url' => $viewScope === 'all_branches'
                ? route('role.relations.activities.show', $activity)
                : (($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                    ? route('role.relations.activities.show', $activity)
                    : ($canOpenEdit ? route('role.relations.activities.edit', $activity) : route('role.relations.activities.show', $activity))),
            'read_only_unified' => $isReadOnlyUnified && ! $canBranchPartialEditUnified,
        ];
    }

    private function canCompleteAfterExecution(MonthlyActivity $activity, ?User $user): bool
    {
        if ($user === null || ! $this->canUseEditRoute($user) || in_array((string) $activity->status, ['post_execution_submitted', 'closed'], true)) {
            return false;
        }

        if ((int) $activity->created_by === (int) $user->id) {
            return true;
        }

        $reviewDecision = (string) data_get($activity->post_execution_payload ?? [], 'review.decision');

        return $user->hasRole('volunteer_coordinator')
            && in_array((string) $activity->status, ['changes_requested', 'rejected'], true)
            && in_array($reviewDecision, ['clarification', 'rejected'], true)
            && (
                (int) ($user->branch_id ?? 0) === (int) $activity->branch_id
                || $user->assignedBranches()->whereKey((int) $activity->branch_id)->exists()
            );
    }

    private function canUseEditRoute(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::EDIT_ROLES);
    }

    private function isReadOnlyUnifiedAgendaActivity(MonthlyActivity $activity): bool
    {
        $activity->loadMissing('agendaEvent');

        return (bool) $activity->is_from_agenda
            && (string) $activity->plan_type === 'unified'
            && (string) optional($activity->agendaEvent)->event_type === 'mandatory';
    }

    private function canBranchEditUnifiedNonCoreFields(MonthlyActivity $activity, ?User $user): bool
    {
        return $this->isReadOnlyUnifiedAgendaActivity($activity)
            && (bool) config('monthly_activity.unified_branch_edit.enabled', true)
            && $user !== null
            && method_exists($user, 'hasBranchScopedMonthlyVisibility')
            && $user->hasBranchScopedMonthlyVisibility()
            && ! empty($user->branch_id)
            && ! $user->hasRole('super_admin');
    }
}
