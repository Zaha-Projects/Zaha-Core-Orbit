<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\MonthlyActivity;
use App\Models\OfficialCorrespondence;
use App\Modules\Events\Support\EventAggregateIdentity;
use LogicException;

class MonthlyActivityOfficialCorrespondenceService
{
    public function sync(MonthlyActivity $activity, array $attributes): OfficialCorrespondence
    {
        $matches = $this->matches($activity);
        if ($matches->count() > 1) {
            throw new LogicException("Conflicting official correspondence identities exist for MonthlyActivity:{$activity->getKey()}.");
        }

        $correspondence = $matches->first() ?? new OfficialCorrespondence([
            'correspondable_type' => EventAggregateIdentity::currentWriteType(MonthlyActivity::class),
            'correspondable_id' => $activity->getKey(),
        ]);
        $correspondence->fill($attributes)->save();
        return $correspondence;
    }

    public function delete(MonthlyActivity $activity): void
    {
        $matches = $this->matches($activity);
        if ($matches->count() > 1) {
            throw new LogicException("Conflicting official correspondence identities exist for MonthlyActivity:{$activity->getKey()}.");
        }
        $matches->first()?->delete();
    }

    private function matches(MonthlyActivity $activity)
    {
        return OfficialCorrespondence::query()
            ->where('correspondable_id', $activity->getKey())
            ->whereIn('correspondable_type', EventAggregateIdentity::acceptedTypes(MonthlyActivity::class))
            ->limit(2)
            ->get();
    }
}
