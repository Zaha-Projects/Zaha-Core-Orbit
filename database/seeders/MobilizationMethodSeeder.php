<?php

namespace Database\Seeders;

use App\Modules\Events\Models\MobilizationMethod;
use Illuminate\Database\Seeder;

class MobilizationMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            'direct_contact' => 'التواصل المباشر',
            'local_community' => 'التنسيق مع المجتمع المحلي',
            'associations' => 'التنسيق مع الجمعيات',
            'schools' => 'التنسيق مع المدارس',
            'social_media' => 'وسائل التواصل الاجتماعي',
            'direct_invitations' => 'دعوات مباشرة',
            'other' => 'أخرى',
        ];

        foreach ($methods as $code => $name) {
            MobilizationMethod::query()->insertOrIgnore([
                'code' => $code, 'created_at' => now(), 'updated_at' => now(),
                'name_ar' => $name, 'name_en' => $code, 'is_other' => $code === 'other', 'is_active' => true,
                'sort_order' => (array_search($code, array_keys($methods), true) + 1) * 10,
            ]);
        }
    }
}
