<?php

namespace App\Services\Kpi\Support;

use App\Modules\Events\Models\AgendaEvent;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Support\EventAggregateIdentity;

/**
 * Read-only compatibility for record-type spellings stored in workflow_instances /
 * workflow_logs / workflow_action_logs. Historical rows are never rewritten and no morph map
 * is registered: KPI queries simply accept every known spelling of an aggregate.
 */
class KpiWorkflowIdentity
{
    /** @return array<int, string> */
    public static function typesFor(string $module): array
    {
        switch ($module) {
            case 'agenda':
                return EventAggregateIdentity::acceptedTypes(AgendaEvent::class);
            case 'monthly':
                return EventAggregateIdentity::acceptedTypes(MonthlyActivity::class);
            case 'ramadan':
                // Ramadan was introduced after the namespace cutover: one spelling only.
                return [RamadanIftar::class];
        }

        return [];
    }

    public static function sqlList(string $module): string
    {
        return KpiSql::literalList(self::typesFor($module));
    }
}
