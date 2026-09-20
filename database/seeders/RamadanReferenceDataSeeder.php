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
            RamadanIftarGiftTypeSeeder::class,
            CanonicalExecutionNeedTypeSeeder::class,
            RamadanPeriodSeeder::class,
            RamadanIftarGuidanceSeeder::class,
        ]);
    }
}
