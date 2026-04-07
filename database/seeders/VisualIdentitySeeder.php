<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentUnit;
use Illuminate\Database\Seeder;

class VisualIdentitySeeder extends Seeder
{
    protected array $colors = ['#E11D48', '#0EA5E9', '#22C55E', '#F59E0B', '#8B5CF6', '#14B8A6', '#F97316', '#3B82F6', '#84CC16', '#EC4899', '#06B6D4', '#A855F7'];
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
