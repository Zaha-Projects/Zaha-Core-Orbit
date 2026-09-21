<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class rightDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EventReferenceDataSeeder::class,
            CanonicalExecutionNeedTypeSeeder::class,
            RamadanReferenceDataSeeder::class,
            CompleteRolePermissionSeeder::class,
        ]);
    }
}
