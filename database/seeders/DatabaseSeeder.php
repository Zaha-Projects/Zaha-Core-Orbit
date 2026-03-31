<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(CenterSeeder::class);
        $this->call(DepartmentUnitSeeder::class);
        $this->call(EventCategorySeeder::class);
        $this->call(TargetGroupSeeder::class);
        $this->call(EvaluationQuestionSeeder::class);

        $this->call(RolePermissionSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(WorkflowSeeder::class);

        $this->call(MovementSeeder::class);
        $this->call(MonthlyKpiSeeder::class);
        $this->call(EventTypeSeeder::class);
        $this->call(EnterpriseDemoSeeder::class);
    }
}
