<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RamadanIftarStagingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EventReferenceDataSeeder::class,
            CanonicalExecutionNeedTypeSeeder::class,
            MobilizationMethodSeeder::class,
            CommunityOrganizationSeeder::class,
            LocalCommunitySeeder::class,
            RamadanDashboardSettingSeeder::class,
            RamadanPeriodSeeder::class,
            RamadanIftarGuidanceSeeder::class,
            RamadanIftarDemoSeeder::class,
        ]);
    }
}
