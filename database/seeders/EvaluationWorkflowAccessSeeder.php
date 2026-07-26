<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class EvaluationWorkflowAccessSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        DB::transaction(function (): void {
            $permissions = collect($this->permissions())->mapWithKeys(function (array $attributes, string $name) {
                $permission = Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    $attributes
                );

                return [$name => $permission];
            });

            foreach ($this->roles() as $roleName => $definition) {
                $role = Role::query()->updateOrCreate(
                    ['name' => $roleName, 'guard_name' => 'web'],
                    ['name_ar' => $definition['name_ar'], 'name_en' => $definition['name_en']]
                );

                // Add the workflow permissions without revoking any permission that
                // was granted previously or is owned by another application module.
                $role->givePermissionTo(
                    collect($definition['permissions'])->map(fn (string $name) => $permissions->get($name))
                );
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissions(): array
    {
        return [
            'agenda.view' => $this->permission('agenda', 'view', 'عرض الأجندة', 'View agenda'),
            'monthly_activities.view' => $this->permission('monthly_activities', 'view', 'عرض الخطة الشهرية', 'View monthly activities'),
            'monthly_activities.view_other_branches' => $this->permission('monthly_activities', 'view_other_branches', 'عرض الخطط الشهرية للفروع الأخرى', 'View other branches monthly plans'),
            'branches.view.own' => $this->permission('branch_scope', 'view_own', 'عرض الفرع الخاص', 'View own branch'),
            'branches.view.all' => $this->permission('branch_scope', 'view_all', 'عرض كل الفروع', 'View all branches'),
            'evaluation.view' => $this->permission('evaluation', 'view', 'عرض التقييم', 'View evaluation'),
            'evaluation.submit' => $this->permission('evaluation', 'submit', 'إرسال التقييم', 'Submit evaluation'),
            'evaluation.view_branch' => $this->permission('evaluation', 'view', 'عرض تقييمات الفرع', 'View branch evaluations'),
            'evaluation.view_all' => $this->permission('evaluation', 'view_all', 'عرض جميع التقييمات', 'View all evaluations'),
            'evaluation.submit_branch' => $this->permission('evaluation', 'submit', 'تقييم نشاط الفرع', 'Submit branch activity evaluation'),
            'evaluation.forms.manage' => $this->permission('evaluation', 'manage', 'إدارة نماذج التقييم', 'Manage evaluation forms'),
            'evaluation.questions.manage' => $this->permission('evaluation', 'manage', 'إدارة أسئلة التقييم', 'Manage evaluation questions'),
            'evaluation.visibility.manage' => $this->permission('evaluation', 'manage', 'إدارة ظهور التقييم', 'Manage evaluation visibility'),
            'post_execution.view_branch' => $this->permission('post_execution', 'view', 'عرض إكمال ما بعد التنفيذ للفرع', 'View branch post-execution'),
            'post_execution.view_all' => $this->permission('post_execution', 'view_all', 'عرض جميع بيانات ما بعد التنفيذ', 'View all post-execution'),
            'post_execution.verify_branch' => $this->permission('post_execution', 'verify', 'التحقق من ما بعد التنفيذ للفرع', 'Verify branch post-execution'),
            'followup.dashboard.view' => $this->permission('followup', 'view', 'عرض لوحة مسؤول المتابعة', 'View follow-up dashboard'),
            'followup.monthly_plans.view' => $this->permission('followup', 'view', 'عرض خطط فرع المتابعة', 'View follow-up branch plans'),
            'followup.post_execution.view' => $this->permission('followup', 'view', 'عرض ما بعد التنفيذ للمتابعة', 'View follow-up post-execution'),
            'followup.post_execution.verify' => $this->permission('followup', 'verify', 'مراجعة ما بعد التنفيذ', 'Verify follow-up post-execution'),
            'followup.evaluations.create' => $this->permission('followup', 'create', 'إنشاء تقييم نشاط', 'Create follow-up evaluation'),
            'followup.evaluations.view' => $this->permission('followup', 'view', 'عرض تقييمات الفرع', 'View follow-up evaluations'),
            'users.directory.view' => $this->permission('directory', 'view', 'عرض دليل المستخدمين', 'View user directory'),
            'profile.view' => $this->permission('profile', 'view', 'عرض الملف الشخصي', 'View profile'),
        ];
    }

    private function roles(): array
    {
        return [
            'followup_officer' => [
                'name_ar' => 'مسؤول المتابعة',
                'name_en' => 'Follow-up Officer',
                'permissions' => [
                    'agenda.view', 'monthly_activities.view', 'branches.view.own',
                    'evaluation.view', 'evaluation.submit', 'evaluation.view_branch', 'evaluation.submit_branch',
                    'post_execution.view_branch', 'post_execution.verify_branch',
                    'followup.dashboard.view', 'followup.monthly_plans.view', 'followup.post_execution.view',
                    'followup.post_execution.verify', 'followup.evaluations.create', 'followup.evaluations.view',
                    'users.directory.view', 'profile.view',
                ],
            ],
            'evaluation_officer' => [
                'name_ar' => 'مسؤول التقييم',
                'name_en' => 'Evaluation Officer',
                'permissions' => [
                    'agenda.view', 'monthly_activities.view', 'monthly_activities.view_other_branches',
                    'branches.view.all', 'evaluation.view', 'evaluation.view_all', 'post_execution.view_all',
                    'followup.monthly_plans.view', 'users.directory.view', 'profile.view',
                ],
            ],
            'relations_officer' => [
                'name_ar' => 'مسؤول العلاقات',
                'name_en' => 'Relations Officer',
                'permissions' => ['evaluation.view_branch', 'evaluation.visibility.manage'],
            ],
            'relations_manager' => [
                'name_ar' => 'مدير علاقات رئيسي',
                'name_en' => 'Primary Relations Manager',
                'permissions' => [
                    'evaluation.view_all', 'evaluation.forms.manage', 'evaluation.questions.manage',
                    'evaluation.visibility.manage', 'post_execution.view_all',
                ],
            ],
        ];
    }

    private function permission(string $module, string $action, string $nameAr, string $nameEn): array
    {
        return ['module' => $module, 'action' => $action, 'name_ar' => $nameAr, 'name_en' => $nameEn];
    }
}
