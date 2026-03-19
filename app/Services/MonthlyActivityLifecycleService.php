<?php

namespace App\Services;

use App\Models\MonthlyActivity;

class MonthlyActivityLifecycleService
{
    /** @var array<string, array<int, string>> */
    protected array $transitions = [
        'Draft' => ['Submitted'],
        'Submitted' => ['Branch Approved', 'Draft'],
        'Branch Approved' => ['Khelda Liaison Approved', 'Submitted'],
        'Khelda Liaison Approved' => ['Khelda Director Approved', 'Branch Approved'],
        'Khelda Director Approved' => ['Exec Director Approved', 'Scheduled', 'Khelda Liaison Approved'],
        'Exec Director Approved' => ['Scheduled', 'Khelda Director Approved'],
        'Scheduled' => ['Executed'],
        'Executed' => ['Evaluated'],
        'Evaluated' => ['Closed'],
        'Closed' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions[$from] ?? [], true);
    }

    public function transitionOrFail(MonthlyActivity $activity, string $to): void
    {
        $from = $activity->lifecycle_status ?: 'Draft';
        abort_unless($this->canTransition($from, $to), 422, "Invalid lifecycle transition from {$from} to {$to}");

        $activity->update(['lifecycle_status' => $to]);
    }
}
