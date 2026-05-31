<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = collect(config('branches.items', []));

        if ($branches->isEmpty()) {
            throw new InvalidArgumentException('No branches are configured in branches.items.');
        }

        $branchIds = $branches->pluck('id')->all();

        Branch::query()->whereNotIn('id', $branchIds)->delete();

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(
                ['id' => $branch['id']],
                [
                    'name' => $branch['name'],
                    'city' => $branch['city'],
                    'address' => $branch['address'],
                    'is_main' => $branch['is_main'],
                ]
            );
        }
    }
}
