<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Modules\Events\Models\RamadanPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RamadanPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $legacy = Setting::query()->whereIn('key', [
            'ramadan_period_year', 'ramadan_period_start_date', 'ramadan_period_end_date', 'ramadan_period_is_active',
        ])->pluck('value', 'key');
        if ($legacy->isNotEmpty() && $legacy->count() < 4) {
            throw new \RuntimeException('Incomplete legacy Ramadan settings; configure the normalized period in Admin.');
        }

        $initial = $legacy->isEmpty()
            ? ['year' => 2026, 'hijri_year' => 1447, 'start_date' => '2026-02-18', 'end_date' => '2026-03-19', 'is_active' => true, 'is_confirmed' => true]
            : ['year' => (int) $legacy['ramadan_period_year'], 'hijri_year' => null, 'start_date' => $legacy['ramadan_period_start_date'], 'end_date' => $legacy['ramadan_period_end_date'], 'is_active' => $legacy['ramadan_period_is_active'] === '1', 'is_confirmed' => true];
        Validator::make($initial, array_merge(RamadanPeriod::rules(), ['hijri_year' => ['nullable', 'integer', 'min:1400', 'max:1600']]))->validate();

        DB::transaction(function () use ($initial): void {
            $period = RamadanPeriod::query()->firstOrCreate(['year' => $initial['year']], $initial);
            if ($initial['is_active'] && ! RamadanPeriod::query()->where('is_active', true)->exists()) $period->activate();
        }, 5);
    }
}
