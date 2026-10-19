<?php

namespace App\Services\Kpi\Modules;

use Illuminate\Database\Query\Builder;

class AgendaKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'agenda';
    }

    protected function table(): string
    {
        return 'agenda_events';
    }

    protected function dateColumn(): string
    {
        return 'event_date';
    }

    /** Live = not soft-deleted and not archived (an approved edit archives its source version). */
    protected function applyLive(Builder $query): void
    {
        $query->whereNull('s.deleted_at')
            ->where('s.is_archived', false)
            ->where('s.status', '<>', 'archived');
    }

    protected function rawStageConditions(): array
    {
        return [
            'planned' => "s.status <> 'draft'",
            // Final approval publishes the event; relations_approved is only a partial step.
            'approved' => "s.status = 'published'",
            'executed' => null,
            'closed' => null,
        ];
    }

    public function unavailableReason(string $stage): ?string
    {
        return in_array($stage, ['executed', 'closed'], true)
            ? 'الأجندة السنوية لا تمتلك مرحلة تنفيذ أو إغلاق؛ يتم التنفيذ عبر الأنشطة الشهرية المرتبطة.'
            : null;
    }

    public function notes(): array
    {
        return [
            'فعاليات الأجندة بدون تاريخ محدد لا تدخل ضمن أي فترة.',
            'نطاق الفرع للأجندة يعتمد على مشاركة الفرع كـ«مشارك» في الفعالية.',
        ];
    }

    /** Agenda events have no branch_id: a branch is in scope when it participates. */
    public function applyBranchScope(Builder $query, ?array $branchIds): void
    {
        if ($branchIds === null) {
            return;
        }

        if ($branchIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereExists(function (Builder $participation) use ($branchIds): void {
            $participation->selectRaw('1')
                ->from('agenda_participations as ap')
                ->whereColumn('ap.agenda_event_id', 's.id')
                ->where('ap.entity_type', 'branch')
                ->where('ap.participation_status', 'participant')
                ->whereIn('ap.entity_id', $branchIds);
        });
    }

    public function joinBranchKey(Builder $query, ?array $branchIds): string
    {
        $query->join('agenda_participations as ap', function ($join): void {
            $join->on('ap.agenda_event_id', '=', 's.id')
                ->where('ap.entity_type', '=', 'branch')
                ->where('ap.participation_status', '=', 'participant');
        });

        if ($branchIds !== null) {
            $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('ap.entity_id', $branchIds);
        }

        return 'ap.entity_id';
    }
}
