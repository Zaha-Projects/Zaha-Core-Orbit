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

    public function findMonthlyActivityConflicts(string $proposedDate, int $branchId, ?int $ignoreActivityId = null, ?string $executionTime = null): array
    {
        $query = MonthlyActivity::query()
            ->whereDate('proposed_date', $proposedDate)
            ->where('branch_id', $branchId);

        if ($ignoreActivityId) {
            $query->where('id', '!=', $ignoreActivityId);
        }

        if (empty($executionTime) || strpos($executionTime, '-') === false) {
            return $query->limit(5)->pluck('title')->all();
        }

        [$newStart, $newEnd] = array_map('trim', explode('-', $executionTime, 2));

        return $query
            ->whereNotNull('execution_time')
            ->get()
            ->filter(function (MonthlyActivity $activity) use ($newStart, $newEnd) {
                if (! str_contains((string) $activity->execution_time, '-')) {
                    return false;
                }

                [$start, $end] = array_map('trim', explode('-', (string) $activity->execution_time, 2));

                return $newStart < $end && $newEnd > $start;
            })
            ->pluck('title')
            ->take(5)
            ->values()
            ->all();
    }
}
