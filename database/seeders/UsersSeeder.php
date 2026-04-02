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

}
