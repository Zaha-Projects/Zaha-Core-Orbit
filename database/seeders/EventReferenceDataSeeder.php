<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EventReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Event categories are scoped to these approved departments.
            DepartmentSeeder::class,
            EventTypeSeeder::class,
            TargetGroupSeeder::class,
            BeneficiarySegmentSeeder::class,
            MonitoringMethodSeeder::class,
            EventStatusLookupSeeder::class,
            // Categories are department-owned reference data, so run them only
            // after the approved department catalogue has been reconciled.
            EventCategorySeeder::class,
        ]);
    }
}
