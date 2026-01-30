<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            ['city' => 'Riyadh', 'address' => 'Main Office']
        );

        $center = Center::firstOrCreate(
            ['name' => 'Main Center', 'branch_id' => $branch->id]
        );

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'super_admin@zaha.test',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Relations Manager',
                'email' => 'relations_manager@zaha.test',
                'role' => 'relations_manager',
            ],
            [
                'name' => 'Relations Officer',
                'email' => 'relations_officer@zaha.test',
                'role' => 'relations_officer',
            ],
            [
                'name' => 'Programs Manager',
                'email' => 'programs_manager@zaha.test',
                'role' => 'programs_manager',
            ],
            [
                'name' => 'Programs Officer',
                'email' => 'programs_officer@zaha.test',
                'role' => 'programs_officer',
            ],
            [
                'name' => 'Finance Officer',
                'email' => 'finance_officer@zaha.test',
                'role' => 'finance_officer',
            ],
            [
                'name' => 'Maintenance Officer',
                'email' => 'maintenance_officer@zaha.test',
                'role' => 'maintenance_officer',
            ],
            [
                'name' => 'Transport Officer',
                'email' => 'transport_officer@zaha.test',
                'role' => 'transport_officer',
            ],
            [
                'name' => 'Reports Viewer',
                'email' => 'reports_viewer@zaha.test',
                'role' => 'reports_viewer',
            ],
            [
                'name' => 'Staff Member',
                'email' => 'staff@zaha.test',
                'role' => 'staff',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => null,
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'center_id' => $center->id,
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
