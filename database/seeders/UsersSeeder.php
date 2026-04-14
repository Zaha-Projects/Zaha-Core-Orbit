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
        }

        return;

        $users = [
            [
                'name' => 'مدير النظام - مروان الخطيب (Marwan Al-Khatib)',
                'email' => 'super-admin@zaha.test',
                'role' => 'super_admin',
                'branch' => 'amman',
                'phone' => '0790001001',
            ],
            [
                'name' => 'المدير التنفيذي - رنا المجالي (Rana Al-Majali)',
                'email' => 'executive-manager@zaha.test',
                'role' => 'executive_manager',
                'branch' => 'amman',
                'phone' => '0790001002',
            ],
            [
                'name' => 'مدير البرامج - ليث الحمود (Laith Al-Hmoud)',
                'email' => 'programs-manager@zaha.test',
                'role' => 'programs_manager',
                'branch' => 'amman',
                'phone' => '0790001003',
            ],
            [
                'name' => 'مدير العلاقات - ديمة السالم (Deema Al-Salem)',
                'email' => 'relations-manager@zaha.test',
                'role' => 'relations_manager',
                'branch' => 'amman',
                'phone' => '0790001004',
            ],
            [
                'name' => 'مسؤول العلاقات - عمر الشوابكة (Omar Al-Shawabkeh)',
                'email' => 'relations-officer-amman@zaha.test',
                'role' => 'relations_officer',
                'branch' => 'amman',
                'phone' => '0790001005',
            ],
            [
                'name' => 'مسؤول علاقات الفروع - نهى الزعبي (Noha Al-Zoubi)',
                'email' => 'branch-relations-officer-irbid@zaha.test',
                'role' => 'branch_relations_officer',
                'branch' => 'irbid',
                'phone' => '0790001006',
            ],
            [
                'name' => 'مسؤول علاقات الفروع - سالم العبداللات (Salem Al-Abdallat)',
                'email' => 'branch-relations-officer-zarqa@zaha.test',
                'role' => 'branch_relations_officer',
                'branch' => 'zarqa',
                'phone' => '0790001007',
            ],
            [
                'name' => 'مسؤول المتابعة - هاجر الرواشدة (Hajar Al-Rawashdeh)',
                'email' => 'followup-officer@zaha.test',
                'role' => 'followup_officer',
                'branch' => 'amman',
                'phone' => '0790001008',
            ],
            [
                'name' => 'سكرتير الورش - ندى الخصاونة (Nada Al-Khasawneh)',
                'email' => 'workshops-secretary@zaha.test',
                'role' => 'workshops_secretary',
                'branch' => 'amman',
                'phone' => '0790001009',
            ],
            [
                'name' => 'مسؤول العلاقات - إسراء السرحان (Esraa Al-Sarhan)',
                'email' => 'relations-officer-zarqa@zaha.test',
                'role' => 'relations_officer',
                'branch' => 'zarqa',
                'phone' => '0790001010',
            ],
            [
                'name' => 'مسؤول العلاقات - محمد عبابنة (Mohammad Ababneh)',
                'email' => 'relations-officer-irbid@zaha.test',
                'role' => 'relations_officer',
                'branch' => 'irbid',
                'phone' => '0790001011',
            ],
        ];

        foreach ($users as $userData) {
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
            ['name' => 'مدير النظام - مروان الخطيب', 'email' => 'super-admin@zaha.test', 'role' => 'super_admin', 'branch' => 'amman', 'phone' => '0790001001'],
            ['name' => 'المدير التنفيذي - رنا المجالي', 'email' => 'executive-manager@zaha.test', 'role' => 'executive_manager', 'branch' => 'amman', 'phone' => '0790001002'],
            ['name' => 'مدير البرامج - ليث الحمود', 'email' => 'programs-manager@zaha.test', 'role' => 'programs_manager', 'branch' => 'amman', 'phone' => '0790001003'],
            ['name' => 'مدير علاقات رئيسي - ديمة السالم', 'email' => 'relations-manager@zaha.test', 'role' => 'relations_manager', 'branch' => 'amman', 'phone' => '0790001004'],
            ['name' => 'مدير علاقات فرعي - يوسف العبادي', 'email' => 'branch-relations-manager@zaha.test', 'role' => 'branch_relations_manager', 'branch' => 'zarqa', 'phone' => '0790001005'],
            ['name' => 'مسؤول علاقات رئيسي - عمر الشوابكة', 'email' => 'relations-officer@zaha.test', 'role' => 'relations_officer', 'branch' => 'amman', 'phone' => '0790001006'],
            ['name' => 'مسؤول علاقات الفروع - نهى الزعبي', 'email' => 'branch-relations-officer@zaha.test', 'role' => 'branch_relations_officer', 'branch' => 'irbid', 'phone' => '0790001007'],
            ['name' => 'مسؤول المتابعة - هاجر الرواشدة', 'email' => 'followup-officer@zaha.test', 'role' => 'followup_officer', 'branch' => 'amman', 'phone' => '0790001008'],
            ['name' => 'سكرتير الورش - ندى الخصاونة', 'email' => 'workshops-secretary@zaha.test', 'role' => 'workshops_secretary', 'branch' => 'amman', 'phone' => '0790001009'],
            ['name' => 'منسق الفروع - أحمد العزام', 'email' => 'branch-coordinator@zaha.test', 'role' => 'branch_coordinator', 'branch' => 'irbid', 'phone' => '0790001010'],
            ['name' => 'رئيس قسم الاتصال - لين القضاة', 'email' => 'communication-head@zaha.test', 'role' => 'communication_head', 'branch' => 'amman', 'phone' => '0790001011'],
            ['name' => 'مسؤول المالية - محمد العموش', 'email' => 'finance-officer@zaha.test', 'role' => 'finance_officer', 'branch' => 'amman', 'phone' => '0790001012'],
            ['name' => 'مسؤول الصيانة - أيهم العكور', 'email' => 'maintenance-officer@zaha.test', 'role' => 'maintenance_officer', 'branch' => 'amman', 'phone' => '0790001013'],
            ['name' => 'مسؤول النقل - يزن العزام', 'email' => 'transport-officer@zaha.test', 'role' => 'transport_officer', 'branch' => 'amman', 'phone' => '0790001014'],
            ['name' => 'مستعرض التقارير - ريم الزبن', 'email' => 'reports-viewer@zaha.test', 'role' => 'reports_viewer', 'branch' => 'amman', 'phone' => '0790001015'],
            ['name' => 'موظف - نور الخطيب', 'email' => 'staff@zaha.test', 'role' => 'staff', 'branch' => 'amman', 'phone' => '0790001016'],
            ['name' => 'مدير الحركة - معتصم الرحامنة', 'email' => 'movement-manager@zaha.test', 'role' => 'movement_manager', 'branch' => 'amman', 'phone' => '0790001017'],
            ['name' => 'محرر الحركة - رامي النعيمات', 'email' => 'movement-editor@zaha.test', 'role' => 'movement_editor', 'branch' => 'amman', 'phone' => '0790001018'],
            ['name' => 'مستعرض الحركة - نورس الحجايا', 'email' => 'movement-viewer@zaha.test', 'role' => 'movement_viewer', 'branch' => 'amman', 'phone' => '0790001019'],
        ];
    }
}
