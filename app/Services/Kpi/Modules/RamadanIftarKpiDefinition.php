<?php

namespace App\Services\Kpi\Modules;

use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Database\Query\Builder;

class RamadanIftarKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'ramadan';
    }

    protected function table(): string
    {
        return 'ramadan_iftars';
    }

    protected function dateColumn(): string
    {
        return 'planned_date';
    }

    /**
     * Live = not soft-deleted and not superseded. An approved change request creates a new
     * version (parent_version_id) while the source keeps its approved status.
     */
    protected function applyLive(Builder $query): void
    {
        $query->whereNull('s.deleted_at')
            ->whereNotExists(function (Builder $newer): void {
                $newer->selectRaw('1')
                    ->from('ramadan_iftars as newer')
                    ->whereNull('newer.deleted_at')
                    ->whereColumn('newer.parent_version_id', 's.id');
            });
    }

    protected function rawStageConditions(): array
    {
        return [
            'planned' => "s.status <> '".RamadanIftar::STATUS_DRAFT."'",
            'approved' => "s.status = '".RamadanIftar::STATUS_APPROVED."'",
            'executed' => "s.execution_status = '".RamadanIftar::EXECUTION_STATUS_COMPLETED."'",
            // Closure is a timestamp, not a status value.
            'closed' => 's.closed_at IS NOT NULL',
        ];
    }
}
