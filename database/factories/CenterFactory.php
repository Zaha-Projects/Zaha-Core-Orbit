<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterFactory extends Factory
{
    protected $model = Center::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => $this->faker->company().' Center',
        ];
    }
}
