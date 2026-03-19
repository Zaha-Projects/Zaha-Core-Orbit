<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    protected array $branchAliases = [
        'khalda' => 'khalda',
        'خلدا' => 'khalda',
        'amman' => 'khalda',
        'عمّان' => 'khalda',
        'عمان' => 'khalda',
        'zarqa' => 'zarqa',
        'زرقاء' => 'zarqa',
        'الزرقاء' => 'zarqa',
        'irbid' => 'irbid',
        'اربد' => 'irbid',
        'إربد' => 'irbid',
    ];

    public function run(): void
    {
        $branchesByCode = $this->resolveBranchesByCode();

        $hqUsers = [
            [
                'name' => 'Khalda Relations Manager',
                'email' => 'relations_manager@khalda.zaha.test',
                'role' => 'relations_manager',
                'branch' => 'khalda',
            ],
            [
                'name' => 'Khalda Programs Officer',
                'email' => 'programs_officer@khalda.zaha.test',
                'role' => 'programs_officer',
                'branch' => 'khalda',
            ],
            [
                'name' => 'Khalda Programs Manager',
                'email' => 'programs_manager@khalda.zaha.test',
                'role' => 'programs_manager',
                'branch' => 'khalda',
            ],
            [
                'name' => 'Executive Manager',
                'email' => 'executive_manager@khalda.zaha.test',
                'role' => 'executive_manager',
                'branch' => 'khalda',
            ],
            [
                'name' => 'Workshops Secretary',
                'email' => 'workshops_secretary@khalda.zaha.test',
                'role' => 'workshops_secretary',
                'branch' => 'khalda',
            ],
            [
                'name' => 'Communication Head',
                'email' => 'communication_head@khalda.zaha.test',
                'role' => 'communication_head',
                'branch' => 'khalda',
            ],
        ];

        $branchCodes = ['zarqa', 'irbid'];
        $branchUsers = collect($branchCodes)
            ->flatMap(function (string $branchCode) {
                $label = ucfirst($branchCode);

                return [
                    [
                        'name' => "{$label} Relations Officer",
                        'email' => "relations_officer@{$branchCode}.zaha.test",
                        'role' => 'relations_officer',
                        'branch' => $branchCode,
                    ],
                    [
                        'name' => "{$label} Relations Manager",
                        'email' => "relations_manager@{$branchCode}.zaha.test",
                        'role' => 'relations_manager',
                        'branch' => $branchCode,
                    ],
                ];
            })
            ->all();

        $users = array_merge($hqUsers, $branchUsers, [
            [
                'name' => 'Super Admin',
                'email' => 'super_admin@zaha.test',
                'role' => 'super_admin',
                'branch' => 'khalda',
            ],
        ]);

        foreach ($users as $userData) {
            $branch = $branchesByCode[$userData['branch']] ?? null;
            if (! $branch) {
                continue;
            }

            $centerId = Center::query()
                ->where('branch_id', $branch->id)
                ->orderBy('id')
                ->value('id');

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => null,
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'center_id' => $centerId,
                    'password' => Hash::make('password'),
                ]
            );

            $user->update([
                'name' => $userData['name'],
                'branch_id' => $branch->id,
                'center_id' => $centerId,
                'status' => 'active',
            ]);

            $user->syncRoles([$userData['role']]);
        }
    }

    /**
     * @return array<string, Branch>
     */
    protected function resolveBranchesByCode(): array
    {
        $mapped = [];

        foreach (Branch::query()->get() as $branch) {
            $tokens = [
                mb_strtolower((string) $branch->name),
                mb_strtolower((string) $branch->city),
            ];

            foreach ($tokens as $token) {
                foreach ($this->branchAliases as $needle => $code) {
                    if ($token !== '' && str_contains($token, mb_strtolower($needle))) {
                        $mapped[$code] = $branch;
                    }
                }
            }
        }

        return $mapped;
    }
}
