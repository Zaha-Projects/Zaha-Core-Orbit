<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RamadanReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TargetGroupSeeder::class,
            BeneficiarySegmentSeeder::class,
            MonitoringMethodSeeder::class,
            MobilizationMethodSeeder::class,
            CommunityOrganizationSeeder::class,
            LocalCommunitySeeder::class,
            RamadanDashboardSettingSeeder::class,
            CanonicalExecutionNeedTypeSeeder::class,
            RamadanPeriodSeeder::class,
            RamadanIftarGuidanceSeeder::class,
        ]);
    }
}
