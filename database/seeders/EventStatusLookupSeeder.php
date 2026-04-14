<?php

namespace Database\Seeders;

use App\Models\EventStatusLookup;
use Illuminate\Database\Seeder;

class EventStatusLookupSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['module' => 'agenda', 'code' => 'draft', 'name' => 'مسودة', 'sort_order' => 10],
            ['module' => 'agenda', 'code' => 'submitted', 'name' => 'مرسل', 'sort_order' => 20],
            ['module' => 'agenda', 'code' => 'relations_review', 'name' => 'قيد مراجعة العلاقات', 'sort_order' => 30],
            ['module' => 'agenda', 'code' => 'relations_approved', 'name' => 'معتمد من مدير العلاقات', 'sort_order' => 40],
            ['module' => 'agenda', 'code' => 'executive_review', 'name' => 'قيد مراجعة المدير التنفيذي', 'sort_order' => 50],
            ['module' => 'agenda', 'code' => 'published', 'name' => 'منشور', 'sort_order' => 60],
            ['module' => 'agenda', 'code' => 'changes_requested', 'name' => 'مطلوب تعديل', 'sort_order' => 70],
            ['module' => 'agenda', 'code' => 'rejected', 'name' => 'مرفوض', 'sort_order' => 80],
            ['module' => 'agenda', 'code' => 'approved', 'name' => 'معتمد', 'sort_order' => 90],
            ['module' => 'agenda', 'code' => 'pending', 'name' => 'قيد الانتظار', 'sort_order' => 100],
            ['module' => 'agenda', 'code' => 'final_approved', 'name' => 'معتمد نهائيًا', 'sort_order' => 110],

            ['module' => 'monthly_activities', 'code' => 'draft', 'name' => 'مسودة', 'sort_order' => 10],
            ['module' => 'monthly_activities', 'code' => 'submitted', 'name' => 'مرسل', 'sort_order' => 20],
            ['module' => 'monthly_activities', 'code' => 'in_review', 'name' => 'قيد المراجعة', 'sort_order' => 30],
            ['module' => 'monthly_activities', 'code' => 'approved', 'name' => 'معتمد', 'sort_order' => 40],
            ['module' => 'monthly_activities', 'code' => 'changes_requested', 'name' => 'مطلوب تعديل', 'sort_order' => 50],
            ['module' => 'monthly_activities', 'code' => 'postponed', 'name' => 'مؤجل', 'sort_order' => 60],
            ['module' => 'monthly_activities', 'code' => 'cancelled', 'name' => 'ملغي', 'sort_order' => 70],
            ['module' => 'monthly_activities', 'code' => 'closed', 'name' => 'مغلق', 'sort_order' => 80],
            ['module' => 'monthly_activities', 'code' => 'completed', 'name' => 'مكتمل', 'sort_order' => 90],
            ['module' => 'monthly_activities', 'code' => 'rejected', 'name' => 'مرفوض', 'sort_order' => 100],
            ['module' => 'monthly_activities', 'code' => 'executed', 'name' => 'تم التنفيذ', 'sort_order' => 110],
        ];

        foreach ($statuses as $status) {
            EventStatusLookup::query()->updateOrCreate(
                ['module' => $status['module'], 'code' => $status['code']],
                [
                    'name' => $status['name'],
                    'is_active' => true,
                    'sort_order' => $status['sort_order'],
                ]
            );
        }
    }
}