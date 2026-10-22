<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * KPI Phase 5 permissions. Additive only: firstOrCreate + givePermissionTo.
 * Never syncs, revokes or deletes existing permissions; safe to run repeatedly.
 *
 * - kpi.users.view: view OTHER users' performance (self view needs no permission).
 * - kpi.export:     CSV export of organisational KPI reports and other users' actions
 *                   (own performance export needs no permission).
 * super_admin already passes every check through Gate::before / its "*" grant.
 */
class KpiPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $definitions = [
            'kpi.users.view' => ['users_view', 'عرض أداء المستخدمين', 'View user performance KPIs'],
            'kpi.export' => ['export', 'تصدير تقارير المؤشرات', 'Export KPI reports'],
        ];

        foreach ($definitions as $name => [$action, $nameAr, $nameEn]) {
            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['module' => 'reports', 'action' => $action, 'name_ar' => $nameAr, 'name_en' => $nameEn]
            );
        }

        // Organisation-wide management roles only. Other roles stay self-only until the business decides.
        $rolePermissions = [
            'super_admin' => ['kpi.users.view', 'kpi.export'],
            'executive_manager' => ['kpi.users.view', 'kpi.export'],
            'relations_manager' => ['kpi.users.view', 'kpi.export'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()->where('guard_name', 'web')->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissionNames);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
