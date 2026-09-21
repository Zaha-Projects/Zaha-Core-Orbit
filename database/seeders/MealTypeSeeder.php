<?php

namespace Database\Seeders;

use App\Modules\Events\Models\MealType;
use Illuminate\Database\Seeder;

class MealTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'main' => ['وجبة رئيسية', 10],
            'side' => ['طبق جانبي', 20],
            'drink' => ['مشروب', 30],
            'dessert' => ['حلويات', 40],
            'other' => ['أخرى', 50],
        ];

        foreach ($types as $code => [$name, $order]) {
            MealType::query()->firstOrCreate(
                ['code' => $code],
                ['name_ar' => $name, 'is_active' => true, 'sort_order' => $order]
            );
        }
    }
}
