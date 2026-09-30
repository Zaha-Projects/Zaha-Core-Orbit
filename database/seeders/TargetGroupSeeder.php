<?php

namespace Database\Seeders;

use App\Modules\Events\Models\TargetGroup;
use Illuminate\Database\Seeder;

class TargetGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = ['children' => 'أطفال', 'youth' => 'شباب', 'elderly' => 'كبار السن', 'other' => 'أخرى'];

        foreach ($groups as $index => $name) {
            $code = array_keys($groups)[$index];
            TargetGroup::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'sort_order' => ($index + 1) * 10,
                'is_other' => $code === 'other',
                'is_active' => true,
                'is_monthly_activity' => true,
                'is_ramadan_iftar' => true,
            ]);
        }

        TargetGroup::query()->whereNotIn('code', array_keys($groups))->update(['is_active' => false]);
    }
}
