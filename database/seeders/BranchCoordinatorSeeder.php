<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class BranchCoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('branches.coordinators', []) as $coordinator) {
            $branchIds = $coordinator['branch_ids'];
            $this->assertBranchesExist($branchIds);

            $user = User::query()->updateOrCreate(
                ['email' => $coordinator['email']],
                [
                    'name' => $coordinator['name'],
                    'phone' => $coordinator['phone'],
                    'status' => 'active',
                    'branch_id' => $branchIds[0] ?? null,
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles(['branch_coordinator']);
            $user->assignedBranches()->sync($branchIds);
        }
    }

    /**
     * @param  array<int, int>  $branchIds
     */
    private function assertBranchesExist(array $branchIds): void
    {
        $existingBranchIds = Branch::query()
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $missingBranchIds = array_values(array_diff($branchIds, $existingBranchIds));

        if ($missingBranchIds !== []) {
            throw new InvalidArgumentException('Unknown branch IDs [' . implode(', ', $missingBranchIds) . '] in branches.coordinators config.');
        }
    }
}
