<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branchNames = [
            'مركز زها الثقافي - الكرك',
            'مركز زها الثقافي - المفرق',
            'مركز زها الثقافي - خلدا',
            'مركز زها الثقافي - إربد',
            'مركز زها الثقافي - مأدبا',
            'مركز زها الثقافي - عجلون',
            'مركز زها الثقافي - ماعين',
            'مركز زها الثقافي - الطفيلة',
            'مركز زها الثقافي - دير علا',
            'مركز زها الثقافي - أبو علندا',
            'مركز زها الثقافي - المنصورة',
            'مركز زها الثقافي - التقوى',
            'مركز زها الثقافي - باب الواد',
            'مركز زها الثقافي - أم عمر الفيصل',
            'مركز زها الثقافي - المستندة',
            'مركز زها الثقافي - جرش',
            'مركز زها الثقافي - طبربور',
            'مركز زها الثقافي - الزهور',
            'مركز زها الثقافي - المشارع',
            'مركز زها الثقافي - الرصيفة',
            'مركز زها الثقافي - الزرقاء',
            'مركز زها الثقافي - معان',
            'مركز زها الثقافي - الرمثا',
            'مركز زها الثقافي - غور الصافي',
            'مركز زها الثقافي - العقبة',
        ];

        $branches = collect($branchNames)
            ->map(fn (string $name): array => [
                'name' => $name,
                'city' => trim((string) Str::of($name)->afterLast('-')),
                'address' => null,
            ])
            ->all();

        Branch::query()->whereNotIn('name', $branchNames)->delete();

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(
                ['name' => $branch['name']],
                [
                    'city' => $branch['city'],
                    'address' => $branch['address'],
                ]
            );
        }
    }
}
