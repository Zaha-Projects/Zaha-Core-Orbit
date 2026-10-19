<?php

namespace App\Services\Kpi\Modules;

use App\Modules\Events\Models\Bazaar;
use Illuminate\Database\Query\Builder;

class BazaarKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'bazaar';
    }

    protected function table(): string
    {
        return 'bazaars';
    }

    protected function dateColumn(): string
    {
        return 'bazaar_date';
    }

    /** Bazaars have no soft deletes or versioning: every row is live. */
    protected function applyLive(Builder $query): void
    {
    }

    protected function rawStageConditions(): array
    {
        return [
            'planned' => "s.status <> '".Bazaar::STATUS_DRAFT."'",
            'approved' => 's.status IN ('.self::quoteList([Bazaar::STATUS_APPROVED, Bazaar::STATUS_EXECUTING, Bazaar::STATUS_POST_EXECUTION, Bazaar::STATUS_VERIFIED, Bazaar::STATUS_COMPLETED]).')',
            'executed' => 's.status IN ('.self::quoteList([Bazaar::STATUS_POST_EXECUTION, Bazaar::STATUS_VERIFIED, Bazaar::STATUS_COMPLETED]).')',
            'closed' => "s.status = '".Bazaar::STATUS_COMPLETED."'",
        ];
    }

    public function notes(): array
    {
        return ['لا يُسجَّل وقت إغلاق البازار؛ الإغلاق يُستدل عليه من الحالة «مكتمل» فقط.'];
    }
}
