<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'العلاقات العامة'],
            ['name' => 'البرامج'],
            ['name' => 'المالية'],
            ['name' => 'الصيانة'],
            ['name' => 'النقل والحركة'],
            ['name' => 'المتابعة والتقييم'],
        ];

        foreach ($departments as $index => $department) {
            Department::updateOrCreate(
                ['name' => $department['name']],
                array_merge($department, [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'color_hex' => $department['color_hex'] ?? '#2563EB',
                    'icon' => $department['icon'] ?? 'DPT',
                ])
            );
        }
    }
}