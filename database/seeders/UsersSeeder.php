<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $branches = $this->resolveBranches();

        foreach ($this->userDefinitions() as $userData) {
            $branch = $branches[$userData['branch']] ?? null;

            if (! $branch) {
                throw new InvalidArgumentException('Unknown branch key [' . $userData['branch'] . '] in UsersSeeder.');
            }

            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
            $user->assignedBranches()->sync($userData['role'] === 'branch_coordinator' ? [$branch->id] : []);
        }
    }

    /**
     * @return array<string, Branch>
     */
    private function resolveBranches(): array
    {
        $branches = Branch::query()->get();

        $fallback = $branches->first();

        return [
            'amman' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'عمّان') || str_contains((string) $branch->city, 'عمان') || str_contains((string) $branch->name, 'خلدا') || str_contains((string) $branch->name, 'طبربور') || str_contains((string) $branch->name, 'أبو علندا')) ?? $fallback,
            'zarqa' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'الزرقاء') || str_contains((string) $branch->city, 'زرقاء') || str_contains((string) $branch->name, 'الزرقاء') || str_contains((string) $branch->name, 'الرصيفة')) ?? $fallback,
            'irbid' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'إربد') || str_contains((string) $branch->city, 'اربد') || str_contains((string) $branch->name, 'إربد') || str_contains((string) $branch->name, 'المشارع')) ?? $fallback,
        ];
    }

    /**
     * @return array<int, array{name:string,email:string,role:string,branch:string,phone:string}>
     */
    private function userDefinitions(): array
    {
        return [
            ['name' => 'مدير النظام - مروان الخطيب', 'email' => 'admin@zaha-center.org', 'role' => 'super_admin', 'branch' => 'amman', 'phone' => '0790001001'],
            ['name' => 'المدير التنفيذي - رانيه صبيح', 'email' => 'executive-manager@zaha.test', 'role' => 'executive_manager', 'branch' => 'amman', 'phone' => '0790001002'],
            ['name' => 'مدير البرامج -  ', 'email' => 'programs-manager@zaha.test', 'role' => 'programs_manager', 'branch' => 'amman', 'phone' => '0790001003'],
            ['name' => 'مدير علاقات رئيسي -  ', 'email' => 'relations-manager@zaha.test', 'role' => 'relations_manager', 'branch' => 'amman', 'phone' => '0790001004'],
            ['name' => 'مسؤول المتابعة -  ', 'email' => 'followup-officer@zaha.test', 'role' => 'followup_officer', 'branch' => 'amman', 'phone' => '0790001008'],
            ['name' => 'رئيس قسم الاتصال -  ', 'email' => 'communication-head@zaha.test', 'role' => 'communication_head', 'branch' => 'amman', 'phone' => '0790001011'],
            ['name' => 'مسؤول المالية -  ', 'email' => 'finance-officer@zaha.test', 'role' => 'finance_officer', 'branch' => 'amman', 'phone' => '0790001012'],
            ['name' => 'مسؤول الصيانة -  ', 'email' => 'maintenance-officer@zaha.test', 'role' => 'maintenance_officer', 'branch' => 'amman', 'phone' => '0790001013'],
            ['name' => 'مسؤول النقل -  ', 'email' => 'transport-officer@zaha.test', 'role' => 'transport_officer', 'branch' => 'amman', 'phone' => '0790001014'],
            ['name' => 'مستعرض التقارير -  ', 'email' => 'reports-viewer@zaha.test', 'role' => 'reports_viewer', 'branch' => 'amman', 'phone' => '0790001015'],
            ['name' => 'موظف -  ', 'email' => 'staff@zaha.test', 'role' => 'staff', 'branch' => 'amman', 'phone' => '0790001016'],
            ['name' => 'مدير الحركة -  ', 'email' => 'movement-manager@zaha.test', 'role' => 'movement_manager', 'branch' => 'amman', 'phone' => '0790001017'],
            ['name' => 'محرر الحركة -  ', 'email' => 'movement-editor@zaha.test', 'role' => 'movement_editor', 'branch' => 'amman', 'phone' => '0790001018'],
            ['name' => 'مستعرض الحركة -  ', 'email' => 'movement-viewer@zaha.test', 'role' => 'movement_viewer', 'branch' => 'amman', 'phone' => '0790001019'],
            ['name' => 'منسق التطوع -  ', 'email' => 'volunteer-coordinator@zaha.test', 'role' => 'volunteer_coordinator', 'branch' => 'amman', 'phone' => '0790001020'],
            ['name' => 'مدير الوحدة الإدارية -  ', 'email' => 'administrative-unit-manager@zaha.test', 'role' => 'administrative_unit_manager', 'branch' => 'amman', 'phone' => '0790001021'],
        ];
    }

}
