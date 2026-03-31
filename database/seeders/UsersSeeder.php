<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Center;
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
                'name' => 'مروان الخطيب - Marwan Al-Khatib',
                'email' => 'm.al-khatib@zaha.test',
                'role' => 'super_admin',
                'branch' => 'amman',
                'phone' => '0790001001',
            ],
            [
                'name' => 'رنا المجالي - Rana Al-Majali',
                'email' => 'r.almajali@zaha.test',
                'role' => 'executive_manager',
                'branch' => 'amman',
                'phone' => '0790001002',
            ],
            [
                'name' => 'ليث الحمود - Laith Al-Hmoud',
                'email' => 'l.alhmoud@zaha.test',
                'role' => 'programs_manager',
                'branch' => 'amman',
                'phone' => '0790001003',
            ],
            [
                'name' => 'ديمة السالم - Deema Al-Salem',
                'email' => 'd.alsalem@zaha.test',
                'role' => 'relations_manager',
                'branch' => 'amman',
                'phone' => '0790001004',
            ],
            [
                'name' => 'عمر الشوابكة - Omar Al-Shawabkeh',
                'email' => 'o.shawabkeh@zaha.test',
                'role' => 'relations_officer',
                'branch' => 'amman',
                'phone' => '0790001005',
            ],
            [
                'name' => 'نهى الزعبي - Noha Al-Zoubi',
                'email' => 'n.alzoubi@zaha.test',
                'role' => 'branch_relations_officer',
                'branch' => 'irbid',
                'phone' => '0790001006',
            ],
            [
                'name' => 'سالم العبداللات - Salem Al-Abdallat',
                'email' => 's.abdallat@zaha.test',
                'role' => 'branch_relations_officer',
                'branch' => 'zarqa',
                'phone' => '0790001007',
            ],
            [
                'name' => 'هاجر الرواشدة - Hajar Al-Rawashdeh',
                'email' => 'h.rawashdeh@zaha.test',
                'role' => 'followup_officer',
                'branch' => 'amman',
                'phone' => '0790001008',
            ],
            [
                'name' => 'ندى الخصاونة - Nada Al-Khasawneh',
                'email' => 'n.khasawneh@zaha.test',
                'role' => 'workshops_secretary',
                'branch' => 'amman',
                'phone' => '0790001009',
            ],
            [
                'name' => 'إسراء السرحان - Esraa Al-Sarhan',
                'email' => 'e.sarhan@zaha.test',
                'role' => 'relations_officer',
                'branch' => 'zarqa',
                'phone' => '0790001010',
            ],
            [
                'name' => 'محمد عبابنة - Mohammad Ababneh',
                'email' => 'm.ababneh@zaha.test',
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

            $centerId = Center::query()->where('branch_id', $branch->id)->orderBy('id')->value('id');

            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'center_id' => $centerId,
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

        return [
            'amman' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'عمّان') || str_contains((string) $branch->city, 'عمان')),
            'zarqa' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'الزرقاء') || str_contains((string) $branch->city, 'زرقاء')),
            'irbid' => $branches->first(fn (Branch $branch): bool => str_contains((string) $branch->city, 'إربد') || str_contains((string) $branch->city, 'اربد')),
        ];
    }
}
