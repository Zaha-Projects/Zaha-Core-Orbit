<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class EvaluationWorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

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
            ['name' => 'followup.dashboard.view', 'module' => 'followup', 'action' => 'view', 'name_ar' => 'عرض لوحة مسؤول المتابعة', 'name_en' => 'View follow-up dashboard'],
            ['name' => 'followup.monthly_plans.view', 'module' => 'followup', 'action' => 'view', 'name_ar' => 'عرض خطط فرع المتابعة', 'name_en' => 'View follow-up branch plans'],
            ['name' => 'followup.post_execution.view', 'module' => 'followup', 'action' => 'view', 'name_ar' => 'عرض ما بعد التنفيذ للمتابعة', 'name_en' => 'View follow-up post-execution'],
            ['name' => 'followup.post_execution.verify', 'module' => 'followup', 'action' => 'verify', 'name_ar' => 'مراجعة ما بعد التنفيذ', 'name_en' => 'Verify follow-up post-execution'],
            ['name' => 'followup.evaluations.create', 'module' => 'followup', 'action' => 'create', 'name_ar' => 'إنشاء تقييم نشاط', 'name_en' => 'Create follow-up evaluation'],
            ['name' => 'followup.evaluations.view', 'module' => 'followup', 'action' => 'view', 'name_ar' => 'عرض تقييمات الفرع', 'name_en' => 'View follow-up evaluations'],
            ['name' => 'users.directory.view', 'module' => 'directory', 'action' => 'view', 'name_ar' => 'عرض دليل المستخدمين', 'name_en' => 'View user directory'],
            ['name' => 'profile.view', 'module' => 'profile', 'action' => 'view', 'name_ar' => 'عرض الملف الشخصي', 'name_en' => 'View profile'],
        ];

        $newPermissionNames = [];
        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission
            );
            if ($model->wasRecentlyCreated) {
                $newPermissionNames[] = $model->name;
            }
        }

        // Permission names are resolved from Spatie's cache when they are granted to
        // roles. Refresh it after firstOrCreate so permissions added by this run are
        // immediately available, while keeping all existing permissions untouched.
        $permissionRegistrar->forgetCachedPermissions();

        $this->assignNewPermissions($newPermissionNames);
    }

    private function assignNewPermissions(array $newPermissionNames): void
    {
        $map = [
            'followup_officer' => [
                'agenda.view', 'monthly_activities.view', 'branches.view.own', 'evaluation.view',
                'evaluation.submit', 'evaluation.view_branch', 'evaluation.submit_branch',
                'post_execution.view_branch', 'post_execution.verify_branch',
                'followup.dashboard.view', 'followup.monthly_plans.view', 'followup.post_execution.view',
                'followup.post_execution.verify', 'followup.evaluations.create', 'followup.evaluations.view',
                'users.directory.view', 'profile.view',
            ],
            'evaluation_officer' => [
                'agenda.view', 'monthly_activities.view', 'monthly_activities.view_other_branches',
                'evaluation.view', 'evaluation.view_all', 'post_execution.view_all', 'branches.view.all',
                'followup.monthly_plans.view',
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

            $role->givePermissionTo($permissionNames);
        }
    }
}
