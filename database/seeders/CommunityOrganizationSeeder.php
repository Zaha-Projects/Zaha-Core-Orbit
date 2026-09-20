<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Modules\Events\Models\CommunityOrganization;
use Illuminate\Database\Seeder;

class CommunityOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->pluck('id')->each(function (int $branchId): void {
            CommunityOrganization::query()->firstOrCreate(
                ['branch_id' => $branchId, 'name' => 'جمعية / مؤسسة شريكة'],
                ['is_active' => true]
            );
        });
    }
}
