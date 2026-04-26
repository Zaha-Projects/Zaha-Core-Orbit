<?php

namespace Database\Seeders;

use App\Models\ZahaTimeOption;
use Illuminate\Database\Seeder;

class ZahaTimeOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            ['code' => 'storyteller', 'name' => 'حكواتي', 'sort_order' => 1],
            ['code' => 'face_painting', 'name' => 'رسم عالوجوه', 'sort_order' => 2],
            ['code' => 'magician', 'name' => 'ساحر', 'sort_order' => 3],
            ['code' => 'telematch', 'name' => 'تلي ماتش', 'sort_order' => 4],
            ['code' => 'characters', 'name' => 'شخصيات', 'sort_order' => 5],
            ['code' => 'other', 'name' => 'أخرى', 'sort_order' => 6],
        ];

        foreach ($options as $option) {
            ZahaTimeOption::query()->updateOrCreate(
                ['code' => $option['code']],
                [
                    'name' => $option['name'],
                    'sort_order' => $option['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
