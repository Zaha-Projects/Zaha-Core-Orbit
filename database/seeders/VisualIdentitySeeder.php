<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentUnit;
use Illuminate\Database\Seeder;

class VisualIdentitySeeder extends Seeder
{
    protected array $colors = ['#2563EB', '#16A34A', '#EA580C', '#7C3AED', '#DB2777', '#0891B2', '#65A30D', '#DC2626', '#0D9488', '#4F46E5'];
    protected array $icons = ['🏢', '📍', '⭐', '🧭', '🎯', '🛰️', '🪄', '🛡️', '🔷', '🔶'];

    public function run(): void
    {
        Branch::query()->orderBy('id')->get()->values()->each(function (Branch $branch, int $index): void {
            $branch->update([
                'color_hex' => $branch->color_hex ?: $this->colors[$index % count($this->colors)],
                'icon' => $branch->icon ?: $this->icons[$index % count($this->icons)],
            ]);
        });

        Department::query()->orderBy('id')->get()->values()->each(function (Department $department, int $index): void {
            $department->update([
                'color_hex' => $department->color_hex ?: $this->colors[$index % count($this->colors)],
                'icon' => $department->icon ?: $this->icons[$index % count($this->icons)],
            ]);
        });

        DepartmentUnit::query()->orderBy('id')->get()->values()->each(function (DepartmentUnit $unit, int $index): void {
            $unit->update([
                'color_hex' => $unit->color_hex ?: $this->colors[$index % count($this->colors)],
                'icon' => $unit->icon ?: $this->icons[$index % count($this->icons)],
            ]);
        });
    }
}
