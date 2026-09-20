<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Modules\Events\Support\RamadanPeriod;
use App\Modules\Events\Models\RamadanPeriod as Period;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Seeder;

class RamadanPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            RamadanPeriod::YEAR_KEY => '2026',
            RamadanPeriod::START_KEY => '2026-02-18',
            RamadanPeriod::END_KEY => '2026-03-19',
            RamadanPeriod::ACTIVE_KEY => '1',
        ];

        DB::transaction(function () use ($defaults): void {
            $settings = Setting::query()->whereIn('key', array_keys($defaults))->pluck('value', 'key');
            if ($settings->isNotEmpty() && $settings->count() < count($defaults)) {
                throw new \RuntimeException('Incomplete legacy Ramadan period settings. Configure all four ramadan_period_* settings before seeding; dates will not be guessed.');
            }
            $values = $settings->isEmpty() ? $defaults : $settings->all();
            $data = [
                'year' => $values[RamadanPeriod::YEAR_KEY],
                'start_date' => $values[RamadanPeriod::START_KEY],
                'end_date' => $values[RamadanPeriod::END_KEY],
                'is_active' => $values[RamadanPeriod::ACTIVE_KEY],
            ];
            Validator::make($data, Period::rules())->validate();
            Setting::query()->insertOrIgnore(collect($values)->map(fn ($value, $key) => [
                'key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now(),
            ])->values()->all());
            // A rerun must never replace administrator-maintained dates.
            Period::query()->insertOrIgnore(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        }, 5);
    }
}
