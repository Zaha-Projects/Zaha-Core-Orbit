<?php

namespace Database\Seeders;

use App\Modules\Events\Models\RamadanIftarGiftType;
use Illuminate\Database\Seeder;

class RamadanIftarGiftTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['gifts' => 'هدايا', 'shields' => 'دروع', 'both' => 'هدايا ودروع'] as $code => $name) {
            RamadanIftarGiftType::query()->insertOrIgnore([
                'code' => $code, 'name_ar' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
