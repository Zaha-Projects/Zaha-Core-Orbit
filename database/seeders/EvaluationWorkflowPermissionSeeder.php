<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class EvaluationWorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'evaluation.view_branch', 'module' => 'evaluation', 'action' => 'view', 'name_ar' => 'عرض تقييمات الفرع', 'name_en' => 'View branch evaluations'],
            ['name' => 'evaluation.view_all', 'module' => 'evaluation', 'action' => 'view_all', 'name_ar' => 'عرض جميع التقييمات', 'name_en' => 'View all evaluations'],
            ['name' => 'evaluation.submit_branch', 'module' => 'evaluation', 'action' => 'submit', 'name_ar' => 'تقييم نشاط الفرع', 'name_en' => 'Submit branch activity evaluation'],
            ['name' => 'evaluation.forms.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة نماذج التقييم', 'name_en' => 'Manage evaluation forms'],
            ['name' => 'evaluation.questions.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة أسئلة التقييم', 'name_en' => 'Manage evaluation questions'],
            ['name' => 'evaluation.visibility.manage', 'module' => 'evaluation', 'action' => 'manage', 'name_ar' => 'إدارة ظهور التقييم', 'name_en' => 'Manage evaluation visibility'],
            ['name' => 'post_execution.view_branch', 'module' => 'post_execution', 'action' => 'view', 'name_ar' => 'عرض إكمال ما بعد التنفيذ للفرع', 'name_en' => 'View branch post-execution'],
            ['name' => 'post_execution.view_all', 'module' => 'post_execution', 'action' => 'view_all', 'name_ar' => 'عرض جميع بيانات ما بعد التنفيذ', 'name_en' => 'View all post-execution'],
            ['name' => 'post_execution.verify_branch', 'module' => 'post_execution', 'action' => 'verify', 'name_ar' => 'التحقق من ما بعد التنفيذ للفرع', 'name_en' => 'Verify branch post-execution'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission + ['guard_name' => 'web']
            );
        }

        $this->syncRolePermissions();
    }

    private function syncRolePermissions(): void
    {
        $map = [
            'followup_officer' => [
                'agenda.view', 'monthly_activities.view', 'branches.view.own', 'evaluation.view',
                'evaluation.submit', 'evaluation.view_branch', 'evaluation.submit_branch',
                'post_execution.view_branch', 'post_execution.verify_branch',
            ],
            'evaluation_officer' => [
                'agenda.view', 'monthly_activities.view', 'monthly_activities.view_other_branches',
                'evaluation.view', 'evaluation.view_all', 'post_execution.view_all', 'branches.view.all',
            ],
            'relations_officer' => ['evaluation.view_branch', 'evaluation.visibility.manage'],
            'relations_manager' => [
                'evaluation.view_all', 'evaluation.forms.manage', 'evaluation.questions.manage',
                'evaluation.visibility.manage', 'post_execution.view_all',
            ],
        ];

        foreach ($map as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                continue;
            }

            if ($roleName === 'followup_officer' || $roleName === 'evaluation_officer') {
                $role->syncPermissions($permissionNames);
            } else {
                $role->givePermissionTo($permissionNames);
            }
        }
    }
}
