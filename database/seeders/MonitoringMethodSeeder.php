<?php

namespace Database\Seeders;

use App\Modules\Events\Models\MonitoringMethod;
use Illuminate\Database\Seeder;

class MonitoringMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'cameras', 'name_ar' => 'كاميرات', 'name_en' => 'Cameras'],
            ['code' => 'field_visit', 'name_ar' => 'زيارة ميدانية', 'name_en' => 'Field Visit'],
        ];

        foreach ($methods as $index => $method) {
            MonitoringMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                [
                    'name_ar' => $method['name_ar'],
                    'name_en' => $method['name_en'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
