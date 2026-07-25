<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EvaluationOfficerUsersSeeder extends Seeder
{
    // Development-only credential; override seeded accounts in deployed environments.
    public const DEVELOPMENT_PASSWORD = 'Password123!';

    public function run(): void
    {
        foreach (range(1, 3) as $number) {
            $user = User::query()->updateOrCreate(
                ['email' => "evaluation.officer.{$number}@zaha.local"],
                [
                    'name' => "مسؤول التقييم {$number}",
                    'branch_id' => null,
                    'status' => 'active',
                    'password' => Hash::make(self::DEVELOPMENT_PASSWORD),
                ]
            );
            $user->syncRoles(['evaluation_officer']);
            $user->assignedBranches()->sync([]);
        }
    }
}
