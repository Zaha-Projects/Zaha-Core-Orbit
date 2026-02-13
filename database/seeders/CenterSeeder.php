<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Center;
use Illuminate\Database\Seeder;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'فرع عمّان' => ['مركز الشميساني', 'مركز المدينة الطبية'],
            'فرع الزرقاء' => ['مركز الأمير حسن'],
            'فرع إربد' => ['مركز الجامعة'],
        ];

        foreach ($definitions as $branchName => $centers) {
            $branch = Branch::where('name', $branchName)->first();
            if (! $branch) {
                continue;
            }

            foreach ($centers as $centerName) {
                Center::firstOrCreate([
                    'branch_id' => $branch->id,
                    'name' => $centerName,
                ]);
            }
        }
    }
}
