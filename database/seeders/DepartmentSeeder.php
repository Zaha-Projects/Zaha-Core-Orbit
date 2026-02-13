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

        foreach ($departments as $department) {
            Department::firstOrCreate(['name' => $department['name']], $department);
        }
    }
}
