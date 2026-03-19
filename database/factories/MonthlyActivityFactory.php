<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyActivityFactory extends Factory
{
    protected $model = MonthlyActivity::class;

    public function definition(): array
    {
        return [
            'month' => 3,
            'day' => 19,
            'title' => $this->faker->sentence(3),
            'proposed_date' => '2026-03-19',
            'location_type' => 'inside_center',
            'status' => 'draft',
            'lifecycle_status' => 'Draft',
            'branch_id' => Branch::factory(),
            'center_id' => Center::factory(),
            'created_by' => User::factory(),
        ];
    }
}
