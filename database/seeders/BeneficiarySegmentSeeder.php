<?php

namespace Database\Seeders;

use App\Modules\Events\Models\BeneficiarySegment;
use Illuminate\Database\Seeder;

/** @deprecated Beneficiary segments are retained only for historical foreign keys. */
class BeneficiarySegmentSeeder extends Seeder
{
    public function run(): void
    {
        BeneficiarySegment::query()->update(['is_active' => false]);
    }
}
