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
            ['name' => 'monthly_plan.approve_level_1', 'module' => 'monthly_plan', 'action' => 'approve_level_1', 'name_ar' => 'اعتماد شهري مستوى 1', 'name_en' => 'Monthly approve level 1'],
            ['name' => 'monthly_plan.approve_level_2', 'module' => 'monthly_plan', 'action' => 'approve_level_2', 'name_ar' => 'اعتماد شهري مستوى 2', 'name_en' => 'Monthly approve level 2'],

            ['name' => 'evaluation.view', 'module' => 'evaluation', 'action' => 'view', 'name_ar' => 'عرض التقييم', 'name_en' => 'View evaluation'],
            ['name' => 'evaluation.submit', 'module' => 'evaluation', 'action' => 'submit', 'name_ar' => 'إرسال التقييم', 'name_en' => 'Submit evaluation'],
            ['name' => 'evaluation.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة التقييم', 'name_en' => 'Manage evaluation'],

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

            ['name' => 'monthly_plan.view', 'module' => 'legacy', 'action' => 'alias', 'name_ar' => 'مرادف عرض الخطة', 'name_en' => 'Legacy monthly view'],
            ['name' => 'monthly_plan.create', 'module' => 'legacy', 'action' => 'alias', 'name_ar' => 'مرادف إنشاء الخطة', 'name_en' => 'Legacy monthly create'],
            ['name' => 'monthly_plan.edit', 'module' => 'legacy', 'action' => 'alias', 'name_ar' => 'مرادف تعديل الخطة', 'name_en' => 'Legacy monthly edit'],
            ['name' => 'monthly_plan.approve', 'module' => 'legacy', 'action' => 'alias', 'name_ar' => 'مرادف اعتماد الخطة', 'name_en' => 'Legacy monthly approve'],
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
