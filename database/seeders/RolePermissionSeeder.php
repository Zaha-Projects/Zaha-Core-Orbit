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

            ['name' => 'ramadan_iftars.view', 'module' => 'ramadan_iftars', 'action' => 'view', 'name_ar' => 'عرض خطط إفطار رمضان', 'name_en' => 'View Ramadan Iftar plans'],
            ['name' => 'ramadan_iftars.create', 'module' => 'ramadan_iftars', 'action' => 'create', 'name_ar' => 'إنشاء خطة إفطار رمضان', 'name_en' => 'Create Ramadan Iftar plans'],
            ['name' => 'ramadan_iftars.edit', 'module' => 'ramadan_iftars', 'action' => 'edit', 'name_ar' => 'تعديل خطة إفطار رمضان', 'name_en' => 'Edit Ramadan Iftar plans'],
            ['name' => 'ramadan_iftars.submit', 'module' => 'ramadan_iftars', 'action' => 'submit', 'name_ar' => 'إرسال خطة إفطار رمضان', 'name_en' => 'Submit Ramadan Iftar plans'],
            ['name' => 'ramadan_iftars.approve', 'module' => 'ramadan_iftars', 'action' => 'approve', 'name_ar' => 'اعتماد خطة إفطار رمضان', 'name_en' => 'Approve Ramadan Iftar plans'],
            ['name' => 'ramadan_iftars.execute', 'module' => 'ramadan_iftars', 'action' => 'execute', 'name_ar' => 'تسجيل تنفيذ إفطار رمضان', 'name_en' => 'Record Ramadan Iftar execution'],
            ['name' => 'ramadan_iftars.monitor', 'module' => 'ramadan_iftars', 'action' => 'monitor', 'name_ar' => 'رصد إفطار رمضان', 'name_en' => 'Monitor Ramadan Iftars'],
            ['name' => 'ramadan_iftars.monitor.review', 'module' => 'ramadan_iftars', 'action' => 'monitor_review', 'name_ar' => 'مراجعة واعتماد متابعة إفطار رمضان', 'name_en' => 'Review Ramadan Iftar monitoring'],
            ['name' => 'ramadan_iftars.close', 'module' => 'ramadan_iftars', 'action' => 'close', 'name_ar' => 'إغلاق الإفطار الرمضاني', 'name_en' => 'Close Ramadan Iftars'],
            ['name' => 'ramadan_iftars.change_request.create', 'module' => 'ramadan_iftars', 'action' => 'change_request_create', 'name_ar' => 'طلب تعديل خطة إفطار معتمدة', 'name_en' => 'Request changes to approved Ramadan plans'],
            ['name' => 'ramadan_iftars.change_request.review', 'module' => 'ramadan_iftars', 'action' => 'change_request_review', 'name_ar' => 'مراجعة طلبات تعديل خطط الإفطار', 'name_en' => 'Review Ramadan plan change requests'],

            ['name' => 'bazaars.view', 'module' => 'bazaars', 'action' => 'view', 'name_ar' => 'عرض البازارات', 'name_en' => 'View bazaars'],
            ['name' => 'bazaars.create', 'module' => 'bazaars', 'action' => 'create', 'name_ar' => 'إنشاء بازار', 'name_en' => 'Create bazaars'],
            ['name' => 'bazaars.edit', 'module' => 'bazaars', 'action' => 'edit', 'name_ar' => 'تعديل بازار', 'name_en' => 'Edit bazaars'],
            ['name' => 'bazaars.submit', 'module' => 'bazaars', 'action' => 'submit', 'name_ar' => 'إرسال بازار', 'name_en' => 'Submit bazaars'],
            ['name' => 'bazaars.approve', 'module' => 'bazaars', 'action' => 'approve', 'name_ar' => 'اعتماد بازار', 'name_en' => 'Approve bazaars'],
            ['name' => 'bazaars.execute', 'module' => 'bazaars', 'action' => 'execute', 'name_ar' => 'تنفيذ بازار', 'name_en' => 'Execute bazaars'],
            ['name' => 'bazaars.post_execution', 'module' => 'bazaars', 'action' => 'post_execution', 'name_ar' => 'إدخال ما بعد تنفيذ البازار', 'name_en' => 'Record bazaar post-execution'],
            ['name' => 'bazaars.monitor', 'module' => 'bazaars', 'action' => 'monitor', 'name_ar' => 'متابعة البازار', 'name_en' => 'Monitor bazaars'],
            ['name' => 'bazaars.discount_review', 'module' => 'bazaars', 'action' => 'discount_review', 'name_ar' => 'اعتماد خصومات البازار', 'name_en' => 'Review bazaar discounts'],
            ['name' => 'bazaars.close', 'module' => 'bazaars', 'action' => 'close', 'name_ar' => 'إغلاق البازار', 'name_en' => 'Close bazaars'],

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
