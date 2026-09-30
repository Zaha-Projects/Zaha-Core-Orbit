<?php

namespace Database\Seeders;

use App\Modules\Events\Models\TargetGroup;
use Illuminate\Database\Seeder;

class TargetGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'children' => 'أطفال',
            'youth' => 'شباب',
            'women' => 'سيدات',
            'disability' => 'ذوي إعاقة',
            'local_community' => 'مجتمع محلي',
            'other' => 'أخرى',
        ];

        $types = [
            'children' => TargetGroup::TYPE_AGE,
            'youth' => TargetGroup::TYPE_AGE,
            'women' => TargetGroup::TYPE_COMMUNITY,
            'disability' => TargetGroup::TYPE_COMMUNITY,
            'local_community' => TargetGroup::TYPE_COMMUNITY,
            'other' => TargetGroup::TYPE_COMMUNITY,
        ];

        $sortOrder = 10;
        foreach ($groups as $code => $name) {
            TargetGroup::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $types[$code],
                'sort_order' => $sortOrder,
                'is_other' => $code === 'other',
                'is_active' => true,
                'is_monthly_activity' => true,
                'is_ramadan_iftar' => true,
            ]);
            $sortOrder += 10;
        }

        TargetGroup::query()->whereNotIn('code', array_keys($groups))->update(['is_active' => false]);
    }
}
