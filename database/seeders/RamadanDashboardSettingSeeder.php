<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class RamadanDashboardSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->firstOrCreate(['key' => 'ramadan_dashboard_enabled'], ['value' => '1']);
    }
}
