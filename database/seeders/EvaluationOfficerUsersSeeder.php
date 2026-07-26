<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EvaluationOfficerUsersSeeder extends Seeder
{
    public const DEVELOPMENT_PASSWORD = 'password';

    public function run(): void
    {
        foreach (range(1, 3) as $index) {
            $evaluationOfficer = User::query()->updateOrCreate(
                ['email' => sprintf('evaluation-officer%02d@zaha.test', $index)],
                [
                    'name' => sprintf('مسؤول التقييم %02d', $index),
                    'phone' => sprintf('076220%04d', $index),
                    'status' => 'active',
                    'branch_id' => null,
                    'password' => Hash::make(self::DEVELOPMENT_PASSWORD),
                ]
            );
            $evaluationOfficer->syncRoles(['evaluation_officer']);
        }
    }
}
