<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\RamadanPeriod;
use Illuminate\Support\Facades\DB;

class RamadanPeriodSyncService
{
    public function __construct(private RamadanPeriodCalculator $calculator) {}

    public function sync(int $year): RamadanPeriod
    {
        $proposal = $this->calculator->calculate($year);
        return DB::transaction(function () use ($year, $proposal): RamadanPeriod {
            $period = RamadanPeriod::query()->where('year', $year)->lockForUpdate()->first();
            if (! $period) {
                return RamadanPeriod::query()->create($proposal + [
                    'start_date' => $proposal['suggested_start_date'],
                    'end_date' => $proposal['suggested_end_date'],
                    'synced_at' => now(), 'is_confirmed' => false, 'is_active' => false,
                ]);
            }
            $period->forceFill(array_merge(
                collect($proposal)->only(['suggested_start_date','suggested_end_date','calculation_source'])->all(),
                ['synced_at' => now()],
                $period->is_confirmed ? [] : ['hijri_year' => $proposal['hijri_year']]
            ))->save();
            return $period->fresh();
        }, 5);
    }
}
