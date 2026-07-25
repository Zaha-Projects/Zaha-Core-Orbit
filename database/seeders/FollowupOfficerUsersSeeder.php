<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FollowupOfficerUsersSeeder extends Seeder
{
    // Development-only credential; override seeded accounts in deployed environments.
    public const DEVELOPMENT_PASSWORD = 'Password123!';

    public function run(): void
    {
        Branch::query()->each(function (Branch $branch) {
            $alreadyAssigned = User::role('followup_officer')
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                        ->orWhereHas('assignedBranches', fn ($assigned) => $assigned->where('branches.id', $branch->id));
                })
                ->exists();

            if ($alreadyAssigned) {
                return;
            }

            $slug = Str::slug($branch->name) ?: (string) $branch->id;
            $user = User::query()->firstOrCreate(
                ['email' => "followup.branch.{$slug}@zaha.local"],
                [
                    'name' => "مسؤول متابعة - {$branch->name}",
                    'branch_id' => $branch->id,
                    'status' => 'active',
                    'password' => Hash::make(self::DEVELOPMENT_PASSWORD),
                ]
            );
            if ($user->wasRecentlyCreated) {
                $user->assignRole('followup_officer');
                $user->assignedBranches()->syncWithoutDetaching([$branch->id]);
            }
        });
    }
}
