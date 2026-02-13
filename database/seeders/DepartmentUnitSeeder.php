<?php

namespace Database\Seeders;

use App\Models\DepartmentUnit;
use Illuminate\Database\Seeder;

class DepartmentUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['unit_key' => 'workshops_committee', 'name' => 'لجنة المشاغل', 'role_name' => 'workshops_secretary'],
            ['unit_key' => 'communication_head', 'name' => 'رئيس قسم الاتصال', 'role_name' => 'communication_head'],
            ['unit_key' => 'khalda_programs_manager', 'name' => 'مدير وحدة البرامج (خلدا)', 'role_name' => 'programs_manager'],
            ['unit_key' => 'khalda_events_relations', 'name' => 'فعاليات خلدا', 'role_name' => 'relations_manager'],
        ];

        foreach ($units as $unit) {
            DepartmentUnit::updateOrCreate(['unit_key' => $unit['unit_key']], $unit);
        }
    }
}
