<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchStaffUsersSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->orderBy('id')->get();

        foreach ($branches as $index => $branch) {
            $sequence = $index + 1;

            $supervisor = User::query()->updateOrCreate(
                ['email' => sprintf('supervisor.branch%02d@zaha.test', $sequence)],
                [
                    'name' => sprintf('رئيس فرع - %s', $branch->city ?: $branch->name),
                    'phone' => sprintf('079220%04d', $sequence),
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'password' => Hash::make('password'),
                ]
            );
            $supervisor->syncRoles(['supervisor']);

            $relationsOfficer = User::query()->updateOrCreate(
                ['email' => sprintf('relations-officer.branch%02d@zaha.test', $sequence)],
                [
                    'name' => sprintf('مسؤول علاقات - %s', $branch->city ?: $branch->name),
                    'phone' => sprintf('078220%04d', $sequence),
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'password' => Hash::make('password'),
                ]
            );
            $relationsOfficer->syncRoles(['relations_officer']);
        }
    }
}
