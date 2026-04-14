<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roleDefinitions() as $roleData) {
            Role::query()->updateOrCreate(
                ['name' => $roleData['key'], 'guard_name' => 'web'],
                [
                    'name_ar' => $roleData['name_ar'],
                    'name_en' => $roleData['name_en'],
                ]
            );
        }

        $this->syncRolePermissions();

        return;

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
            [
                'key' => 'programs_officer',
                'name_ar' => 'مسؤول البرامج',
                'name_en' => 'Programs Officer',
            ],
            [
                'key' => 'communication_head',
                'name_ar' => 'رئيس قسم الاتصال',
                'name_en' => 'Communication Head',
            ],
            [
                'key' => 'finance_officer',
                'name_ar' => 'مسؤول المالية',
                'name_en' => 'Finance Officer',
            ],
            [
                'key' => 'maintenance_officer',
                'name_ar' => 'مسؤول الصيانة',
                'name_en' => 'Maintenance Officer',
            ],
            [
                'key' => 'transport_officer',
                'name_ar' => 'مسؤول النقل',
                'name_en' => 'Transport Officer',
            ],
            [
                'key' => 'reports_viewer',
                'name_ar' => 'مستعرض التقارير',
                'name_en' => 'Reports Viewer',
            ],
            [
                'key' => 'staff',
                'name_ar' => 'موظف',
                'name_en' => 'Staff',
            ],
            [
                'key' => 'liaison',
                'name_ar' => 'منسق',
                'name_en' => 'Liaison',
            ],
            [
                'key' => 'movement_manager',
                'name_ar' => 'مدير الحركة',
                'name_en' => 'Movement Manager',
            ],
            [
                'key' => 'movement_editor',
                'name_ar' => 'محرر الحركة',
                'name_en' => 'Movement Editor',
            ],
            [
                'key' => 'movement_viewer',
                'name_ar' => 'مستعرض الحركة',
                'name_en' => 'Movement Viewer',
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
        $map = $this->rolePermissionMap();
        $allPermissions = Permission::query()->where('guard_name', 'web')->get();

        foreach ($map as $roleKey => $permissionNames) {
            $role = Role::query()->where('guard_name', 'web')->where('name', $roleKey)->first();

            if (! $role) {
                continue;
            }

            if ($permissionNames === ['*']) {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $role->syncPermissions($permissions);
        }

        return;

        $map = [
            'super_admin' => ['*'],
            'executive_manager' => ['agenda.view', 'agenda.approve', 'monthly_activities.view', 'monthly_activities.view_other_branches', 'monthly_activities.approve', 'branches.view.all', 'reports.view'],
            'programs_manager' => ['monthly_activities.view', 'monthly_activities.view_other_branches', 'monthly_activities.approve', 'evaluation.manage', 'branches.view.all', 'reports.view', 'kpi.view'],
            'relations_manager' => ['agenda.view', 'agenda.create', 'agenda.update', 'agenda.approve', 'monthly_activities.view', 'monthly_activities.view_other_branches', 'monthly_activities.approve', 'branches.view.all'],
            'branch_relations_officer' => ['agenda.view', 'monthly_activities.view', 'monthly_activities.create', 'monthly_activities.edit', 'branches.view.own', 'communications.upload_media'],
            'relations_officer' => ['agenda.view', 'agenda.create', 'agenda.update', 'monthly_activities.view', 'monthly_activities.create', 'monthly_activities.edit', 'branches.view.own'],
            'followup_officer' => ['reports.view', 'kpi.view', 'kpi.manage', 'agenda.view', 'monthly_activities.view', 'monthly_activities.view_other_branches', 'evaluation.view', 'branches.view.all'],
            'workshops_secretary' => ['agenda.view', 'agenda.participation.update', 'monthly_activities.view', 'monthly_activities.view_other_branches', 'branches.view.all'],
            'programs_officer' => ['monthly_activities.view', 'monthly_activities.view_other_branches', 'monthly_activities.approve', 'monthly_activities.edit', 'branches.view.all', 'reports.view'],
            'communication_head' => ['agenda.view', 'monthly_activities.view', 'monthly_activities.view_other_branches', 'branches.view.all'],
            'finance_officer' => ['reports.view'],
            'maintenance_officer' => [],
            'transport_officer' => [],
            'reports_viewer' => ['reports.view', 'kpi.view'],
            'staff' => ['agenda.view', 'monthly_activities.view'],
            'liaison' => ['monthly_activities.view', 'monthly_activities.view_other_branches', 'monthly_activities.approve', 'branches.view.all'],
            'movement_manager' => [],
            'movement_editor' => [],
            'movement_viewer' => [],
        ];

        $allPermissions = Permission::query()->where('guard_name', 'web')->get();

        foreach ($map as $roleKey => $permissionNames) {
            $role = Role::query()->where('guard_name', 'web')->where('name', $roleKey)->first();

            if (! $role) {
                continue;
            }

            if ($permissionNames === ['*']) {
                $role->syncPermissions($allPermissions);

                continue;
            }

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissionNames)
                ->get();

            $role->syncPermissions($permissions);
        }
    }

    /**
     * @return array<int, array{key:string,name_ar:string,name_en:string}>
     */
    private function roleDefinitions(): array
    {
        return [
            ['key' => 'super_admin', 'name_ar' => 'مدير النظام', 'name_en' => 'System Administrator'],
            ['key' => 'executive_manager', 'name_ar' => 'المدير التنفيذي', 'name_en' => 'Executive Manager'],
            ['key' => 'programs_manager', 'name_ar' => 'مدير البرامج', 'name_en' => 'Programs Manager'],
            ['key' => 'relations_manager', 'name_ar' => 'مدير علاقات رئيسي', 'name_en' => 'Primary Relations Manager'],
            ['key' => 'branch_relations_manager', 'name_ar' => 'مدير علاقات فرعي', 'name_en' => 'Branch Relations Manager'],
            ['key' => 'branch_relations_officer', 'name_ar' => 'مسؤول علاقات الفروع', 'name_en' => 'Branch Relations Officer'],
            ['key' => 'relations_officer', 'name_ar' => 'مسؤول علاقات رئيسي', 'name_en' => 'Primary Relations Officer'],
            ['key' => 'followup_officer', 'name_ar' => 'مسؤول المتابعة', 'name_en' => 'Follow-up Officer'],
            ['key' => 'workshops_secretary', 'name_ar' => 'سكرتير الورش', 'name_en' => 'Workshops Secretary'],
            ['key' => 'branch_coordinator', 'name_ar' => 'منسق الفروع', 'name_en' => 'Branch Coordinator'],
            ['key' => 'communication_head', 'name_ar' => 'رئيس قسم الاتصال', 'name_en' => 'Communication Head'],
            ['key' => 'finance_officer', 'name_ar' => 'مسؤول المالية', 'name_en' => 'Finance Officer'],
            ['key' => 'maintenance_officer', 'name_ar' => 'مسؤول الصيانة', 'name_en' => 'Maintenance Officer'],
            ['key' => 'transport_officer', 'name_ar' => 'مسؤول النقل', 'name_en' => 'Transport Officer'],
            ['key' => 'reports_viewer', 'name_ar' => 'مستعرض التقارير', 'name_en' => 'Reports Viewer'],
            ['key' => 'staff', 'name_ar' => 'موظف', 'name_en' => 'Staff'],
            ['key' => 'movement_manager', 'name_ar' => 'مدير الحركة', 'name_en' => 'Movement Manager'],
            ['key' => 'movement_editor', 'name_ar' => 'محرر الحركة', 'name_en' => 'Movement Editor'],
            ['key' => 'movement_viewer', 'name_ar' => 'مستعرض الحركة', 'name_en' => 'Movement Viewer'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissionMap(): array
    {
        return [
            'super_admin' => ['*'],
            'executive_manager' => [
                'agenda.view',
                'agenda.approve',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.approve',
                'branches.view.all',
                'communications.view_media',
                'evaluation.view',
                'reports.view',
                'kpi.view',
                'kpi.manage',
            ],
            'programs_manager' => [
                'agenda.view',
                'agenda.approve',
                'agenda.participation.update',
                'communications.view_media',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.approve',
                'branches.view.all',
                'reports.view',
                'kpi.view',
            ],
            'relations_manager' => [
                'agenda.view',
                'agenda.create',
                'agenda.update',
                'agenda.delete',
                'agenda.approve',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.create',
                'monthly_activities.edit',
                'monthly_activities.delete',
                'monthly_activities.approve',
                'branches.view.all',
                'communications.view_media',
                'reports.view',
                'kpi.view',
            ],
            'branch_relations_manager' => [
                'agenda.view',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.create',
                'monthly_activities.view_other_branches',
                'monthly_activities.edit',
                'monthly_activities.delete',
                'monthly_activities.approve',
                'branches.view.own',
                'communications.view_media',
            ],
            'branch_relations_officer' => [
                'agenda.view',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.create',
                'monthly_activities.view_other_branches',
                'monthly_activities.edit',
                'monthly_activities.delete',
                'branches.view.own',
                'communications.view_media',
                'communications.upload_media',
            ],
            'relations_officer' => [
                'agenda.view',
                'agenda.create',
                'agenda.update',
                'agenda.delete',
                'agenda.approve',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.create',
                'monthly_activities.edit',
                'monthly_activities.delete',
                'monthly_activities.approve',
                'branches.view.all',
                'communications.view_media',
                'communications.upload_media',
            ],
            'followup_officer' => [
                'agenda.view',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'branches.view.all',
                'evaluation.view',
                'evaluation.submit',
                'reports.view',
                'kpi.view',
                'kpi.manage',
            ],
            'workshops_secretary' => [
                'agenda.view',
                'agenda.approve',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.approve',
                'branches.view.all',
                'reports.view',
                'kpi.view',
            ],
            'branch_coordinator' => [
                'agenda.view',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.create',
                'monthly_activities.view_other_branches',
                'monthly_activities.edit',
                'monthly_activities.delete',
                'monthly_activities.approve',
                'branches.view.own',
                'communications.view_media',
            ],
            'communication_head' => [
                'agenda.view',
                'agenda.approve',
                'agenda.participation.update',
                'monthly_activities.view',
                'monthly_activities.view_other_branches',
                'monthly_activities.approve',
                'branches.view.all',
                'communications.view_media',
                'communications.upload_media',
                'reports.view',
                'kpi.view',
            ],
            'finance_officer' => ['reports.view'],
            'maintenance_officer' => [],
            'transport_officer' => [],
            'reports_viewer' => ['reports.view', 'kpi.view'],
            'staff' => ['agenda.view', 'monthly_activities.view'],
            'movement_manager' => [],
            'movement_editor' => [],
            'movement_viewer' => [],
        ];
    }
}
