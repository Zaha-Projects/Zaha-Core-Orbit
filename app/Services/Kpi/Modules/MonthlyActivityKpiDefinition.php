<?php

namespace App\Services\Kpi\Modules;

use Illuminate\Database\Query\Builder;

class MonthlyActivityKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'monthly';
    }

    protected function table(): string
    {
        return 'monthly_activities';
    }

    protected function dateColumn(): string
    {
        return 'proposed_date';
    }

    /**
     * Live = not soft-deleted, not archived, and the newest version. An approved edit request
     * archives its source; a post-approval edit starts a newer draft version that points back
     * through previous_version_id / parent_version_id.
     */
    protected function applyLive(Builder $query): void
    {
        $query->whereNull('s.deleted_at')
            ->where('s.is_archived', false)
            ->where('s.status', '<>', 'archived')
            ->whereNotExists(function (Builder $newer): void {
                $newer->selectRaw('1')
                    ->from('monthly_activities as newer')
                    ->whereNull('newer.deleted_at')
                    ->where(function (Builder $link): void {
                        $link->whereColumn('newer.previous_version_id', 's.id')
                            ->orWhereColumn('newer.parent_version_id', 's.id');
                    });
            });
    }

    protected function rawStageConditions(): array
    {
        return [
            'planned' => "s.status <> 'draft'",
            'approved' => 's.status IN ('.self::quoteList(['approved', 'post_execution_submitted', 'closed', 'evaluated']).')'
                .' OR s.lifecycle_status IN ('.self::quoteList(['Approved', 'Scheduled', 'Executed', 'Evaluated', 'Closed']).')',
            'executed' => "s.execution_status = 'executed'"
                .' OR s.status IN ('.self::quoteList(['post_execution_submitted', 'closed', 'evaluated']).')',
            // Supervisor approval of post-execution closes the activity; evaluation happens after closure.
            'closed' => 's.status IN ('.self::quoteList(['closed', 'evaluated']).')',
        ];
    }
}
