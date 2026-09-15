<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Modules\Events\Support\RamadanPeriod;
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

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
