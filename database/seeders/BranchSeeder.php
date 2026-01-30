<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'فرع عمّان',
                'city' => 'عمّان',
                'address' => 'شارع المدينة الطبية - الشميساني',
            ],
            [
                'name' => 'فرع الزرقاء',
                'city' => 'الزرقاء',
                'address' => 'شارع الجيش - حي الأمير حسن',
            ],
            [
                'name' => 'فرع إربد',
                'city' => 'إربد',
                'address' => 'شارع الجامعة - حي الحي الشرقي',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
