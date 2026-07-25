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
        User::role('followup_officer')->get()->each->removeRole('followup_officer');

        Branch::query()->each(function (Branch $branch) {
            $slug = Str::slug($branch->name) ?: (string) $branch->id;
            $user = User::query()->updateOrCreate(
                ['email' => "followup.branch.{$slug}@zaha.local"],
                [
                    'name' => "مسؤول متابعة - {$branch->name}",
                    'branch_id' => $branch->id,
                    'status' => 'active',
                    'password' => Hash::make(self::DEVELOPMENT_PASSWORD),
                ]
            );
            $user->syncRoles(['followup_officer']);
            $user->assignedBranches()->sync([$branch->id]);
        });
    }
}
