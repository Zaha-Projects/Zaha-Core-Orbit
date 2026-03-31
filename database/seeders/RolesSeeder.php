<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'key' => 'super_admin',
                'name_ar' => 'مدير النظام',
                'name_en' => 'Super Administrator',
            ],
            [
                'key' => 'executive_manager',
                'name_ar' => 'المدير التنفيذي',
                'name_en' => 'Executive Manager',
            ],
            [
                'key' => 'programs_manager',
                'name_ar' => 'مدير البرامج',
                'name_en' => 'Programs Manager',
            ],
            [
                'key' => 'relations_manager',
                'name_ar' => 'مدير العلاقات',
                'name_en' => 'Relations Manager',
            ],
            [
                'key' => 'branch_relations_officer',
                'name_ar' => 'مسؤول علاقات الفروع',
                'name_en' => 'Branch Relations Officer',
            ],
            [
                'key' => 'relations_officer',
                'name_ar' => 'مسؤول العلاقات',
                'name_en' => 'Relations Officer',
            ],
            [
                'key' => 'followup_officer',
                'name_ar' => 'مسؤول المتابعة',
                'name_en' => 'Follow-up Officer',
            ],
            [
                'key' => 'workshops_secretary',
                'name_ar' => 'سكرتير الورش',
                'name_en' => 'Workshops Secretary',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(
                ['name' => $roleData['key'], 'guard_name' => 'web'],
                [
                    'name_ar' => $roleData['name_ar'],
                    'name_en' => $roleData['name_en'],
                ]
            );
        }

        $this->syncRolePermissions();
    }

    private function syncRolePermissions(): void
    {
        $map = [
            'super_admin' => ['*'],
            'executive_manager' => ['agenda.view', 'agenda.approve', 'monthly_activities.view', 'monthly_activities.approve', 'branches.view.all', 'reports.view'],
            'programs_manager' => ['monthly_activities.view', 'monthly_activities.approve', 'evaluation.manage', 'branches.view.all', 'reports.view', 'kpi.view'],
            'relations_manager' => ['agenda.view', 'agenda.create', 'agenda.update', 'agenda.approve', 'monthly_activities.view', 'monthly_activities.approve', 'branches.view.all'],
            'branch_relations_officer' => ['agenda.view', 'monthly_activities.view', 'monthly_activities.create', 'monthly_activities.edit', 'branches.view.own', 'communications.upload_media'],
            'relations_officer' => ['agenda.view', 'agenda.create', 'agenda.update', 'monthly_activities.view', 'monthly_activities.create', 'monthly_activities.edit', 'branches.view.own'],
            'followup_officer' => ['reports.view', 'kpi.view', 'kpi.manage', 'agenda.view', 'monthly_activities.view', 'evaluation.view', 'branches.view.all'],
            'workshops_secretary' => ['agenda.view', 'agenda.participation.update', 'monthly_activities.view', 'branches.view.all'],
        ];

        $allPermissions = \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        foreach ($map as $roleKey => $permissionNames) {
            $role = Role::query()->where('guard_name', 'web')->where('name', $roleKey)->first();

            if (! $role) {
                continue;
            }

            if ($permissionNames === ['*']) {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $role->syncPermissions($permissionNames);
        }
    }
}
