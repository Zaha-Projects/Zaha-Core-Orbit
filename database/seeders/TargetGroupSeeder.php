<?php

namespace Database\Seeders;

use App\Models\TargetGroup;
use Illuminate\Database\Seeder;

class TargetGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = ['أطفال', 'شباب', 'سيدات', 'ذوي إعاقة', 'مجتمع محلي', 'أخرى'];

        foreach ($groups as $index => $name) {
            TargetGroup::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $index + 1, 'is_other' => $name === 'أخرى']
            );
        }
    }
}
