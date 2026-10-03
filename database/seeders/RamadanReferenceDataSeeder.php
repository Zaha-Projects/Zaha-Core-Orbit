<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RamadanReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MobilizationMethodSeeder::class,
            CommunityOrganizationSeeder::class,
            LocalCommunitySeeder::class,
            MealTypeSeeder::class,
            RamadanPeriodSeeder::class,
            RamadanIftarGuidanceSeeder::class,
            RamadanDashboardSettingSeeder::class,
        ]);
    }
}
