<?php

namespace Database\Seeders;

use App\Modules\Events\Models\TargetGroup;
use Illuminate\Database\Seeder;

class TargetGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = ['children' => 'أطفال', 'youth' => 'شباب', 'women' => 'سيدات', 'disabilities' => 'ذوي إعاقة', 'local_community' => 'مجتمع محلي', 'other' => 'أخرى'];

        foreach ($groups as $code => $name) {
            TargetGroup::query()->insertOrIgnore(
                [
                    'code' => $code, 'created_at' => now(), 'updated_at' => now(),
                    'name' => $name,
                    'sort_order' => array_search($code, array_keys($groups), true) + 1,
                    'is_other' => $name === 'أخرى',
                    'is_active' => true,
                ]
            );
        }
    }
}
