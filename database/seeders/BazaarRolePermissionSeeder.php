<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BazaarRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $definitions = [
            'bazaars.view' => ['view', 'عرض البازارات', 'View bazaars'],
            'bazaars.create' => ['create', 'إنشاء بازار', 'Create bazaars'],
            'bazaars.edit' => ['edit', 'تعديل بازار', 'Edit bazaars'],
            'bazaars.submit' => ['submit', 'إرسال بازار', 'Submit bazaars'],
            'bazaars.approve' => ['approve', 'اعتماد بازار', 'Approve bazaars'],
            'bazaars.execute' => ['execute', 'تنفيذ بازار', 'Execute bazaars'],
            'bazaars.post_execution' => ['post_execution', 'إدخال ما بعد تنفيذ البازار', 'Record bazaar post-execution'],
            'bazaars.monitor' => ['monitor', 'متابعة البازار', 'Monitor bazaars'],
            'bazaars.discount_review' => ['discount_review', 'اعتماد خصومات البازار', 'Review bazaar discounts'],
            'bazaars.close' => ['close', 'إغلاق البازار', 'Close bazaars'],
        ];

        foreach ($definitions as $name => [$action, $nameAr, $nameEn]) {
            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['module' => 'bazaars', 'action' => $action, 'name_ar' => $nameAr, 'name_en' => $nameEn]
            );
        }

        $rolePermissions = [
            'super_admin' => array_keys($definitions),
            'supervisor' => ['bazaars.view', 'bazaars.approve', 'bazaars.discount_review', 'bazaars.close'],
            'relations_officer' => ['bazaars.view', 'bazaars.create', 'bazaars.edit', 'bazaars.submit', 'bazaars.execute', 'bazaars.post_execution'],
            'followup_officer' => ['bazaars.view', 'bazaars.monitor'],
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
