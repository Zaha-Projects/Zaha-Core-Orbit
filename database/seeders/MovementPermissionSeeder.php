<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Modules\Movement\Support\MovementPermission as P;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Movement permissions (plan §14.1) and their default role grants (plan §14.2, D3/D12 approved).
 *
 * Additive and idempotent: permissions are upserted by (name, guard_name) and roles only ever
 * receive grants. Nothing is revoked, so manual or per-user grants survive re-runs.
 * Runs from rightDatabaseSeeder after CompleteRolePermissionSeeder, so every role already exists.
 */
class MovementPermissionSeeder extends Seeder
{
    private const GUARD = 'web';

    /** @var array<string, array{0: string, 1: string}> name => [name_ar, name_en] */
    private const LABELS = [
        P::REQUESTS_CREATE => ['إنشاء طلب حركة', 'Create movement requests'],
        P::REQUESTS_VIEW_ALL => ['عرض جميع طلبات الحركة', 'View all movement requests'],
        P::REQUESTS_REVIEW => ['مراجعة واعتماد طلبات الحركة', 'Review movement requests'],
        P::TRIPS_PLAN => ['تخطيط الرحلات وتعيين المركبات والسائقين', 'Plan trips and assign resources'],
        P::TRIPS_PUBLISH => ['جدولة ونشر الرحلات', 'Schedule and publish trips'],
        P::TRIPS_MERGE => ['دمج الرحلات', 'Merge trips'],
        P::TRIPS_CANCEL => ['إلغاء الرحلات', 'Cancel trips'],
        P::TRIPS_EXECUTE => ['تسجيل تنفيذ الرحلات', 'Record trip execution'],
        P::BOOKINGS_MANAGE => ['إدارة حجوزات الرحلات', 'Manage trip bookings'],
        P::VEHICLES_MANAGE => ['إدارة المركبات', 'Manage vehicles'],
        P::DRIVERS_MANAGE => ['إدارة السائقين', 'Manage drivers'],
        P::DRIVERS_VIEW_DETAILS => ['عرض بيانات السائقين', 'View driver details'],
        P::LOCATIONS_MANAGE => ['إدارة مواقع الحركة', 'Manage movement locations'],
        P::REPORTS_VIEW => ['عرض تقارير الحركة', 'View movement reports'],
        P::DRIVER_WORKSPACE => ['مساحة عمل السائق', 'Driver workspace'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (P::all() as $name) {
            [$nameAr, $nameEn] = self::LABELS[$name];

            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                ['module' => 'movement', 'action' => substr($name, strlen('movement.')), 'name_ar' => $nameAr, 'name_en' => $nameEn]
            );
        }

        foreach (self::rolePermissions() as $roleName => $permissionNames) {
            $role = Role::query()->where('guard_name', self::GUARD)->where('name', $roleName)->first();

            if (! $role) {
                $this->command?->warn("MovementPermissionSeeder: role [{$roleName}] not found; its movement grants were skipped.");

                continue;
            }

            $role->givePermissionTo($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Default grants, exactly plan §14.2 (Revision 3).
     *
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        return [
            'super_admin' => P::all(),
            'movement_manager' => array_values(array_diff(P::all(), [P::REQUESTS_CREATE, P::DRIVER_WORKSPACE])),
            'relations_officer' => [P::REQUESTS_CREATE],
            'supervisor' => [P::REQUESTS_CREATE],
            'executive_manager' => [P::REPORTS_VIEW],
            'movement_driver' => [P::DRIVER_WORKSPACE],
        ];
    }
}
