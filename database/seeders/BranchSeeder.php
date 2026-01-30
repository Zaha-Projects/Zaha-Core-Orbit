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
                'name' => 'فرع الرياض',
                'city' => 'الرياض',
                'address' => 'شارع الملك فهد - حي العليا',
            ],
            [
                'name' => 'فرع جدة',
                'city' => 'جدة',
                'address' => 'طريق المدينة - حي الرويس',
            ],
            [
                'name' => 'فرع الدمام',
                'city' => 'الدمام',
                'address' => 'طريق الخليج - حي الشاطئ',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
