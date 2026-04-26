<?php

namespace Database\Seeders;

use App\Models\ExecutionNeedType;
use Illuminate\Database\Seeder;

class ExecutionNeedTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'ceremony', 'name' => 'أجندة الحفل', 'sort_order' => 10],
            ['code' => 'transport', 'name' => 'تأمين المواصلات', 'sort_order' => 20],
            ['code' => 'maintenance', 'name' => 'الصيانة بالموقع', 'sort_order' => 30],
            ['code' => 'gifts', 'name' => 'الهدايا والدروع', 'sort_order' => 40],
            ['code' => 'programs', 'name' => 'مشاركة البرامج', 'sort_order' => 50],
            ['code' => 'certificates', 'name' => 'الشهادات', 'sort_order' => 60],
            ['code' => 'thanks_letters', 'name' => 'كتب الشكر', 'sort_order' => 70],
            ['code' => 'invitations', 'name' => 'بطاقات الدعوة', 'sort_order' => 80],
        ];

        foreach ($types as $type) {
            ExecutionNeedType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => 'تهيئة أساسية لربط الحاجة بسايكل مستقل لاحقاً.',
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
