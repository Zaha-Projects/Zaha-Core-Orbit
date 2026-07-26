<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class CompleteRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            // Keep the established permission and role catalogues as the canonical
            // source, then layer the evaluation workflow additions on top.
            $this->call(RolePermissionSeeder::class);
            $this->call(RolesSeeder::class);
            $this->call(EvaluationWorkflowAccessSeeder::class);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
