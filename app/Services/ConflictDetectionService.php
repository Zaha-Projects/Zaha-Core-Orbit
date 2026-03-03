<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\MonthlyActivity;

class ConflictDetectionService
{
    public function findAgendaConflicts(string $eventDate, array $branchIds, ?int $ignoreEventId = null): array
    {
        $query = AgendaEvent::query()
            ->whereDate('event_date', $eventDate)
            ->whereHas('participations', function ($q) use ($branchIds) {
                $q->where('entity_type', 'branch')->whereIn('entity_id', $branchIds);
            });

        if ($ignoreEventId) {
            $query->where('id', '!=', $ignoreEventId);
        }

        return $query->limit(5)->pluck('event_name')->all();
    }

    public function findMonthlyActivityConflicts(string $proposedDate, int $branchId, ?int $ignoreActivityId = null): array
    {
        $query = MonthlyActivity::query()
            ->whereDate('proposed_date', $proposedDate)
            ->where('branch_id', $branchId);

        if ($ignoreActivityId) {
            $query->where('id', '!=', $ignoreActivityId);
        }

        return $query->limit(5)->pluck('title')->all();
    }
}
