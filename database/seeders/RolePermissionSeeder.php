<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'agenda.view',
            'agenda.create',
            'agenda.approve',
            'agenda.publish',
            'monthly.view',
            'monthly.create',
            'monthly.approve',
            'monthly.execute',
            'revenues.view',
            'revenues.collect',
            'maintenance.view',
            'maintenance.manage',
            'transport.view',
            'transport.manage',
            'reports.view',
            'kpi.view',
            'agenda.participation.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'super_admin' => $permissions,
            'relations_manager' => ['agenda.view', 'agenda.approve', 'agenda.publish', 'monthly.view', 'monthly.approve'],
            'relations_officer' => ['agenda.view', 'agenda.create', 'monthly.view', 'monthly.approve'],
            'programs_manager' => ['monthly.view', 'monthly.approve', 'monthly.execute'],
            'programs_officer' => ['monthly.view', 'monthly.create'],
            'finance_officer' => ['revenues.view', 'revenues.collect'],
            'maintenance_officer' => ['maintenance.view', 'maintenance.manage'],
            'transport_officer' => ['transport.view', 'transport.manage'],
            'executive_manager' => ['agenda.view', 'agenda.approve', 'agenda.publish', 'monthly.view', 'monthly.approve'],
            'followup_officer' => ['reports.view', 'kpi.view', 'agenda.view', 'monthly.view'],
            'communication_head' => ['agenda.view', 'agenda.participation.update'],
            'workshops_secretary' => ['agenda.view', 'agenda.participation.update'],
            'reports_viewer' => ['reports.view'],
            'staff' => ['agenda.view', 'monthly.view'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
