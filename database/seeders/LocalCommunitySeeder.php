<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Modules\Events\Models\LocalCommunity;
use Illuminate\Database\Seeder;

class LocalCommunitySeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->pluck('id')->each(function (int $branchId): void {
            LocalCommunity::query()->firstOrCreate(
                ['branch_id' => $branchId, 'name' => 'المجتمع المحلي المحيط بالمركز'],
                ['is_active' => true]
            );
        });
    }
}
