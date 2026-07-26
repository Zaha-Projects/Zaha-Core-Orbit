<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FollowupOfficerUsersSeeder extends Seeder
{
    public const DEVELOPMENT_PASSWORD = 'password';

    public function run(): void
    {
        $branches = Branch::query()->orderBy('id')->get();
        $logRows = [];

        foreach ($branches as $index => $branch) {
            $sequence = $index + 1;

            $followupOfficer = User::query()->updateOrCreate(
                ['email' => sprintf('followup-officer.branch%02d@zaha.test', $sequence)],
                [
                    'name' => sprintf('مسؤول متابعة - %s', $branch->city ?: $branch->name),
                    'phone' => sprintf('077220%04d', $sequence),
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'password' => Hash::make(self::DEVELOPMENT_PASSWORD),
                ]
            );
            $followupOfficer->syncRoles(['followup_officer']);
            $followupOfficer->assignedBranches()->sync([$branch->id]);
            $logRows[] = [$followupOfficer->name, $followupOfficer->email, $branch->name, 'followup_officer', 'مسؤول المتابعة'];
        }

        $this->command?->table(['Name', 'Email', 'Branch', 'Role Code', 'Role Name'], $logRows);
    }
}
