<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'العلاقات العامة' => ['فعالية مجتمعية', 'شراكة استراتيجية', 'حملة اتصال'],
            'البرامج' => ['ورشة تدريبية', 'نشاط ترفيهي', 'برنامج توعوي'],
            'النقل والحركة' => ['تنسيق حركة', 'إسناد لوجستي'],
        ];

        foreach ($map as $departmentName => $categories) {
            $department = Department::where('name', $departmentName)->first();
            if (! $department) {
                continue;
            }

            foreach ($categories as $categoryName) {
                EventCategory::updateOrCreate(
                    ['department_id' => $department->id, 'name' => $categoryName],
                    ['active' => true]
                );
            }
        }
    }
}
