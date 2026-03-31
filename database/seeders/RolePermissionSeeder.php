<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'agenda.view', 'module' => 'agenda', 'action' => 'view', 'name_ar' => 'عرض الأجندة', 'name_en' => 'View agenda'],
            ['name' => 'agenda.create', 'module' => 'agenda', 'action' => 'create', 'name_ar' => 'إنشاء الأجندة', 'name_en' => 'Create agenda'],
            ['name' => 'agenda.edit', 'module' => 'agenda', 'action' => 'edit', 'name_ar' => 'تعديل الأجندة', 'name_en' => 'Edit agenda'],
            ['name' => 'agenda.delete', 'module' => 'agenda', 'action' => 'delete', 'name_ar' => 'حذف الأجندة', 'name_en' => 'Delete agenda'],
            ['name' => 'agenda.approve', 'module' => 'agenda', 'action' => 'approve', 'name_ar' => 'اعتماد الأجندة', 'name_en' => 'Approve agenda'],
            ['name' => 'agenda.participation.update', 'module' => 'agenda', 'action' => 'update', 'name_ar' => 'تحديث مشاركة الأجندة', 'name_en' => 'Update agenda participation'],

            ['name' => 'monthly_plan.view', 'module' => 'monthly_plan', 'action' => 'view', 'name_ar' => 'عرض الخطة الشهرية', 'name_en' => 'View monthly plan'],
            ['name' => 'monthly_plan.create', 'module' => 'monthly_plan', 'action' => 'create', 'name_ar' => 'إنشاء الخطة الشهرية', 'name_en' => 'Create monthly plan'],
            ['name' => 'monthly_plan.edit', 'module' => 'monthly_plan', 'action' => 'edit', 'name_ar' => 'تعديل الخطة الشهرية', 'name_en' => 'Edit monthly plan'],
            ['name' => 'monthly_plan.delete', 'module' => 'monthly_plan', 'action' => 'delete', 'name_ar' => 'حذف الخطة الشهرية', 'name_en' => 'Delete monthly plan'],
            ['name' => 'monthly_plan.approve', 'module' => 'monthly_plan', 'action' => 'approve', 'name_ar' => 'اعتماد الخطة الشهرية', 'name_en' => 'Approve monthly plan'],

            ['name' => 'evaluation.view', 'module' => 'evaluation', 'action' => 'view', 'name_ar' => 'عرض التقييم', 'name_en' => 'View evaluation'],
            ['name' => 'evaluation.submit', 'module' => 'evaluation', 'action' => 'submit', 'name_ar' => 'إرسال التقييم', 'name_en' => 'Submit evaluation'],
            ['name' => 'evaluation.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة التقييم', 'name_en' => 'Manage evaluation'],

            ['name' => 'communications.view_media', 'module' => 'communications', 'action' => 'view_media', 'name_ar' => 'عرض المواد الإعلامية', 'name_en' => 'View media'],
            ['name' => 'communications.upload_media', 'module' => 'communications', 'action' => 'upload_media', 'name_ar' => 'رفع مواد إعلامية', 'name_en' => 'Upload media'],

            ['name' => 'revenues.view', 'module' => 'finance', 'action' => 'view', 'name_ar' => 'عرض الإيرادات', 'name_en' => 'View revenues'],
            ['name' => 'revenues.collect', 'module' => 'finance', 'action' => 'collect', 'name_ar' => 'تحصيل الإيرادات', 'name_en' => 'Collect revenues'],
            ['name' => 'maintenance.view', 'module' => 'maintenance', 'action' => 'view', 'name_ar' => 'عرض الصيانة', 'name_en' => 'View maintenance'],
            ['name' => 'maintenance.manage', 'module' => 'maintenance', 'action' => 'manage', 'name_ar' => 'إدارة الصيانة', 'name_en' => 'Manage maintenance'],
            ['name' => 'transport.view', 'module' => 'transport', 'action' => 'view', 'name_ar' => 'عرض النقل', 'name_en' => 'View transport'],
            ['name' => 'transport.manage', 'module' => 'transport', 'action' => 'manage', 'name_ar' => 'إدارة النقل', 'name_en' => 'Manage transport'],
            ['name' => 'movement.view', 'module' => 'movement', 'action' => 'view', 'name_ar' => 'عرض الحركة', 'name_en' => 'View movement'],
            ['name' => 'movement.manage', 'module' => 'movement', 'action' => 'manage', 'name_ar' => 'إدارة الحركة', 'name_en' => 'Manage movement'],
            ['name' => 'reports.view', 'module' => 'reports', 'action' => 'view', 'name_ar' => 'عرض التقارير', 'name_en' => 'View reports'],
            ['name' => 'kpi.view', 'module' => 'kpi', 'action' => 'view', 'name_ar' => 'عرض المؤشرات', 'name_en' => 'View KPIs'],
            ['name' => 'kpi.manage', 'module' => 'kpi', 'action' => 'manage', 'name_ar' => 'إدارة المؤشرات', 'name_en' => 'Manage KPIs'],

            ['name' => 'branches.view.all', 'module' => 'branch_scope', 'action' => 'view_all', 'name_ar' => 'عرض كل الفروع', 'name_en' => 'View all branches'],
            ['name' => 'branches.view.own', 'module' => 'branch_scope', 'action' => 'view_own', 'name_ar' => 'عرض الفرع الخاص فقط', 'name_en' => 'View own branch only'],
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

        $roles = [
            'super_admin' => collect($permissions)->pluck('name')->all(),
            'relations_manager' => ['agenda.view', 'agenda.create', 'agenda.edit', 'agenda.approve', 'monthly_plan.view', 'monthly_plan.approve', 'branches.view.all'],
            'relations_officer' => ['agenda.view', 'agenda.create', 'agenda.edit', 'monthly_plan.view', 'monthly_plan.create', 'monthly_plan.edit', 'branches.view.own'],
            'branch_relations_officer' => ['agenda.view', 'monthly_plan.view', 'monthly_plan.create', 'monthly_plan.edit', 'monthly_plan.approve', 'communications.upload_media', 'branches.view.own'],
            'programs_manager' => ['monthly_plan.view', 'monthly_plan.approve', 'evaluation.manage', 'branches.view.all'],
            'programs_officer' => ['monthly_plan.view', 'monthly_plan.create', 'evaluation.submit', 'communications.upload_media', 'branches.view.all'],
            'finance_officer' => ['revenues.view', 'revenues.collect', 'branches.view.all'],
            'maintenance_officer' => ['maintenance.view', 'maintenance.manage', 'branches.view.all'],
            'transport_officer' => ['transport.view', 'transport.manage', 'movement.view', 'movement.manage', 'branches.view.all'],
            'movement_manager' => ['movement.view', 'movement.manage', 'branches.view.all'],
            'movement_editor' => ['movement.view', 'movement.manage', 'branches.view.all'],
            'movement_viewer' => ['movement.view', 'branches.view.all'],
            'executive_manager' => ['agenda.view', 'agenda.approve', 'monthly_plan.view', 'monthly_plan.approve', 'branches.view.all'],
            'followup_officer' => ['reports.view', 'kpi.view', 'kpi.manage', 'agenda.view', 'monthly_plan.view', 'evaluation.view', 'branches.view.all'],
            'communication_head' => ['agenda.view', 'agenda.participation.update', 'communications.view_media', 'communications.upload_media', 'branches.view.all'],
            'workshops_secretary' => ['agenda.view', 'agenda.participation.update', 'monthly_plan.view', 'branches.view.all'],
            'reports_viewer' => ['reports.view', 'branches.view.all'],
            'staff' => ['agenda.view', 'monthly_plan.view', 'evaluation.view', 'branches.view.own'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $workflow = Workflow::query()->updateOrCreate(
            ['code' => 'monthly_activity_approval'],
            [
                'module' => 'monthly_plan',
                'name_ar' => 'اعتماد الخطة الشهرية',
                'name_en' => 'Monthly Plan Approval',
                'is_active' => true,
            ]
        );

        $steps = [
            ['step_order' => 1, 'step_key' => 'branch_relations_officer_review', 'name_ar' => 'اعتماد علاقات الفرع', 'name_en' => 'Branch Relations Review', 'step_type' => 'sub', 'role' => 'branch_relations_officer', 'permission' => 'monthly_plan.approve'],
            ['step_order' => 2, 'step_key' => 'branch_relations_manager_review', 'name_ar' => 'اعتماد مدير علاقات الفرع', 'name_en' => 'Branch Relations Manager Review', 'step_type' => 'sub', 'role' => 'relations_manager', 'permission' => 'monthly_plan.approve'],
            ['step_order' => 3, 'step_key' => 'hq_programs_manager_review', 'name_ar' => 'اعتماد مدير البرامج الرئيسي', 'name_en' => 'HQ Programs Manager Review', 'step_type' => 'main', 'role' => 'programs_manager', 'permission' => 'monthly_plan.approve'],
            ['step_order' => 4, 'step_key' => 'executive_review', 'name_ar' => 'الاعتماد التنفيذي', 'name_en' => 'Executive Review', 'step_type' => 'main', 'role' => 'executive_manager', 'permission' => 'monthly_plan.approve'],
        ];

        foreach ($steps as $stepData) {
            WorkflowStep::query()->updateOrCreate(
                ['workflow_id' => $workflow->id, 'step_key' => $stepData['step_key']],
                [
                    'step_order' => $stepData['step_order'],
                    'name_ar' => $stepData['name_ar'],
                    'name_en' => $stepData['name_en'],
                    'step_type' => $stepData['step_type'],
                    'role_id' => optional(Role::query()->where('name', $stepData['role'])->first())->id,
                    'permission_id' => optional(Permission::query()->where('name', $stepData['permission'])->first())->id,
                    'is_editable' => true,
                ]
            );
        }
    }
}
