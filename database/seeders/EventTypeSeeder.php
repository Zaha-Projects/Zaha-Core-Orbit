<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Workshop', 'Campaign', 'Training', 'Official Correspondence Reason'] as $index => $name) {
            EventType::firstOrCreate(['name' => $name], ['sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
