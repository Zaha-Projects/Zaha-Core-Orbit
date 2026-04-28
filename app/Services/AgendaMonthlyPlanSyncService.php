<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AgendaMonthlyPlanSyncService
{
    /**
     * @param array<int|string, string> $branchParticipation
     * @param array<string, mixed> $monthlyTemplate
     */
    public function syncUnifiedAgendaMonthlyActivities(
        AgendaEvent $agendaEvent,
        array $branchParticipation,
        int $actorId,
        array $monthlyTemplate = []
    ): void {
        if ((string) ($agendaEvent->plan_type ?? 'non_unified') !== 'unified') {
            return;
        }

        if (! (bool) ($agendaEvent->is_active ?? true)) {
            return;
        }

        $resolvedDate = $this->resolveAgendaEventDate($agendaEvent);
        $participantBranchIds = $this->participantBranchIds($branchParticipation);

        if ($participantBranchIds->isEmpty()) {
            return;
        }

        MonthlyActivity::query()
            ->where('agenda_event_id', $agendaEvent->id)
            ->whereNotIn('branch_id', $participantBranchIds->all())
            ->update([
                'agenda_event_id' => null,
                'is_in_agenda' => false,
                'is_from_agenda' => false,
                'participation_status' => 'not_participant',
            ]);

        foreach ($participantBranchIds as $branchId) {
            $monthlyActivity = MonthlyActivity::firstOrNew([
                'agenda_event_id' => $agendaEvent->id,
                'branch_id' => $branchId,
            ]);

            $monthlyActivity->fill([
                'month' => (int) Carbon::parse($resolvedDate)->format('m'),
                'day' => (int) Carbon::parse($resolvedDate)->format('d'),
                'title' => (string) ($monthlyTemplate['title'] ?? $agendaEvent->event_name),
                'proposed_date' => $this->resolvedProposedDate($monthlyActivity, $monthlyTemplate, $resolvedDate),
                'is_in_agenda' => true,
                'is_from_agenda' => true,
                'participation_status' => 'participant',
                'plan_type' => (string) ($agendaEvent->plan_type ?? 'unified'),
                'description' => $monthlyTemplate['description'] ?? $agendaEvent->notes,
                'responsible_party' => $monthlyTemplate['responsible_party'] ?? $monthlyActivity->responsible_party,
                'target_group' => $monthlyTemplate['target_group'] ?? $monthlyActivity->target_group,
                'execution_time' => $monthlyTemplate['execution_time'] ?? $monthlyActivity->execution_time,
                'location_details' => $monthlyTemplate['location_details'] ?? $monthlyActivity->location_details,
                'required_volunteers' => $monthlyTemplate['required_volunteers'] ?? $monthlyActivity->required_volunteers,
                'location_type' => $monthlyActivity->location_type ?? 'inside_center',
                'status' => $monthlyActivity->status ?? 'draft',
                'created_by' => (int) ($monthlyActivity->created_by ?: $actorId),
            ]);

            $monthlyActivity->save();
        }
    }

    /**
     * @param array<int|string, string> $branchParticipation
     * @return Collection<int, int>
     */
    protected function participantBranchIds(array $branchParticipation): Collection
    {
        return collect($branchParticipation)
            ->filter(fn ($status): bool => (string) $status === 'participant')
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }

    /**
     * @param array<string, mixed> $monthlyTemplate
     */
    protected function resolvedProposedDate(MonthlyActivity $monthlyActivity, array $monthlyTemplate, string $resolvedDate): string
    {
        return (string) ($monthlyTemplate['proposed_date']
            ?? optional($monthlyActivity->proposed_date)->toDateString()
            ?? $resolvedDate);
    }

    protected function resolveAgendaEventDate(AgendaEvent $agendaEvent): string
    {
        return optional($agendaEvent->event_date)?->toDateString()
            ?? Carbon::create(now()->year, (int) $agendaEvent->month, (int) $agendaEvent->day)->toDateString();
    }
}

