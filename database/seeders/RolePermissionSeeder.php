<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'agenda.view', 'module' => 'agenda', 'action' => 'view', 'name_ar' => 'عرض الأجندة', 'name_en' => 'View agenda'],
            ['name' => 'agenda.create', 'module' => 'agenda', 'action' => 'create', 'name_ar' => 'إنشاء الأجندة', 'name_en' => 'Create agenda'],
            ['name' => 'agenda.update', 'module' => 'agenda', 'action' => 'update', 'name_ar' => 'تعديل الأجندة', 'name_en' => 'Edit agenda'],
            ['name' => 'agenda.delete', 'module' => 'agenda', 'action' => 'delete', 'name_ar' => 'حذف الأجندة', 'name_en' => 'Delete agenda'],
            ['name' => 'agenda.approve', 'module' => 'agenda', 'action' => 'approve', 'name_ar' => 'اعتماد الأجندة', 'name_en' => 'Approve agenda'],
            ['name' => 'agenda.participation.update', 'module' => 'agenda', 'action' => 'update', 'name_ar' => 'تحديث المشاركة', 'name_en' => 'Update participation'],

            ['name' => 'monthly_activities.view', 'module' => 'monthly_activities', 'action' => 'view', 'name_ar' => 'عرض الخطة الشهرية', 'name_en' => 'View monthly activities'],
            ['name' => 'monthly_activities.create', 'module' => 'monthly_activities', 'action' => 'create', 'name_ar' => 'إنشاء الخطة الشهرية', 'name_en' => 'Create monthly activities'],
            ['name' => 'monthly_activities.edit', 'module' => 'monthly_activities', 'action' => 'edit', 'name_ar' => 'تعديل الخطة الشهرية', 'name_en' => 'Edit monthly activities'],
            ['name' => 'monthly_activities.delete', 'module' => 'monthly_activities', 'action' => 'delete', 'name_ar' => 'حذف الخطة الشهرية', 'name_en' => 'Delete monthly activities'],
            ['name' => 'monthly_activities.approve', 'module' => 'monthly_activities', 'action' => 'approve', 'name_ar' => 'اعتماد الخطة الشهرية', 'name_en' => 'Approve monthly activities'],

            ['name' => 'evaluation.view', 'module' => 'evaluation', 'action' => 'view', 'name_ar' => 'عرض التقييم', 'name_en' => 'View evaluation'],
            ['name' => 'evaluation.submit', 'module' => 'evaluation', 'action' => 'submit', 'name_ar' => 'إرسال التقييم', 'name_en' => 'Submit evaluation'],
            ['name' => 'evaluation.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة التقييم', 'name_en' => 'Manage evaluation'],
            ['name' => 'evaluation.view_branch', 'module' => 'evaluation', 'action' => 'view', 'name_ar' => 'عرض تقييمات الفرع', 'name_en' => 'View branch evaluations'],
            ['name' => 'evaluation.view_all', 'module' => 'evaluation', 'action' => 'view_all', 'name_ar' => 'عرض جميع التقييمات', 'name_en' => 'View all evaluations'],
            ['name' => 'evaluation.submit_branch', 'module' => 'evaluation', 'action' => 'submit', 'name_ar' => 'تقييم نشاط الفرع', 'name_en' => 'Submit branch activity evaluation'],
            ['name' => 'evaluation.forms.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة نماذج التقييم', 'name_en' => 'Manage evaluation forms'],
            ['name' => 'evaluation.questions.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة أسئلة التقييم', 'name_en' => 'Manage evaluation questions'],
            ['name' => 'evaluation.visibility.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة ظهور التقييم', 'name_en' => 'Manage evaluation visibility'],
            ['name' => 'post_execution.view_branch', 'module' => 'post_execution', 'action' => 'view', 'name_ar' => 'عرض إكمال ما بعد التنفيذ للفرع', 'name_en' => 'View branch post-execution'],
            ['name' => 'post_execution.view_all', 'module' => 'post_execution', 'action' => 'view_all', 'name_ar' => 'عرض جميع بيانات ما بعد التنفيذ', 'name_en' => 'View all post-execution'],
            ['name' => 'post_execution.verify_branch', 'module' => 'post_execution', 'action' => 'verify', 'name_ar' => 'التحقق من ما بعد التنفيذ للفرع', 'name_en' => 'Verify branch post-execution'],

            ['name' => 'communications.view_media', 'module' => 'communications', 'action' => 'view_media', 'name_ar' => 'عرض الوسائط', 'name_en' => 'View media'],
            ['name' => 'communications.upload_media', 'module' => 'communications', 'action' => 'upload_media', 'name_ar' => 'رفع الوسائط', 'name_en' => 'Upload media'],

            ['name' => 'users.view', 'module' => 'access', 'action' => 'view', 'name_ar' => 'عرض المستخدمين', 'name_en' => 'View users'],
            ['name' => 'users.manage', 'module' => 'access', 'action' => 'manage', 'name_ar' => 'إدارة المستخدمين', 'name_en' => 'Manage users'],
            ['name' => 'roles.view', 'module' => 'access', 'action' => 'view', 'name_ar' => 'عرض الأدوار', 'name_en' => 'View roles'],
            ['name' => 'roles.manage', 'module' => 'access', 'action' => 'manage', 'name_ar' => 'إدارة الأدوار', 'name_en' => 'Manage roles'],
            ['name' => 'workflows.manage', 'module' => 'access', 'action' => 'manage', 'name_ar' => 'إدارة الـ Workflow', 'name_en' => 'Manage workflows'],
            ['name' => 'branches.manage', 'module' => 'access', 'action' => 'manage', 'name_ar' => 'إدارة الفروع', 'name_en' => 'Manage branches'],

            ['name' => 'reports.view', 'module' => 'reports', 'action' => 'view', 'name_ar' => 'عرض التقارير', 'name_en' => 'View reports'],
            ['name' => 'kpi.view', 'module' => 'reports', 'action' => 'view', 'name_ar' => 'عرض المؤشرات', 'name_en' => 'View KPIs'],
            ['name' => 'kpi.manage', 'module' => 'reports', 'action' => 'manage', 'name_ar' => 'إدارة المؤشرات', 'name_en' => 'Manage KPIs'],

            ['name' => 'branches.view.all', 'module' => 'branch_scope', 'action' => 'view_all', 'name_ar' => 'عرض كل الفروع', 'name_en' => 'View all branches'],
            ['name' => 'branches.view.own', 'module' => 'branch_scope', 'action' => 'view_own', 'name_ar' => 'عرض الفرع الخاص', 'name_en' => 'View own branch'],

            ['name' => 'monthly_activities.view_other_branches', 'module' => 'monthly_activities', 'action' => 'view_other_branches', 'name_ar' => 'عرض الخطط الشهرية للفروع الأخرى', 'name_en' => 'View other branches monthly plans'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'name_ar' => $permission['name_ar'],
                    'name_en' => $permission['name_en'],
                    'module' => $permission['module'],
                    'action' => $permission['action'],
                ]
            );
        }
    }
}
