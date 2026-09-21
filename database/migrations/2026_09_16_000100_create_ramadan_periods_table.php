<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_periods', function (Blueprint $table) {
            $table->id();
            // Gregorian season identifier, matching the existing settings contract.
            $table->unsignedSmallInteger('year')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['is_active', 'start_date', 'end_date']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ramadan_periods ADD CONSTRAINT ramadan_period_dates_check CHECK (end_date >= start_date)');
        }

        $settings = DB::table('settings')->whereIn('key', [
            'ramadan_period_year', 'ramadan_period_start_date',
            'ramadan_period_end_date', 'ramadan_period_is_active',
        ])->pluck('value', 'key');
        $year = $settings['ramadan_period_year'] ?? null;
        $start = $settings['ramadan_period_start_date'] ?? null;
        $end = $settings['ramadan_period_end_date'] ?? null;

        // Do not guess dates or silently activate an invalid legacy configuration.
        $validDate = static function ($value): bool {
            $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            return $date && $date->format('Y-m-d') === $value;
        };
        if (ctype_digit((string) $year) && (int) $year >= 2020 && (int) $year <= 2100 && $validDate($start) && $validDate($end) && $start <= $end) {
            DB::table('ramadan_periods')->insert([
                'year' => (int) $year, 'start_date' => $start, 'end_date' => $end,
                'is_active' => ($settings['ramadan_period_is_active'] ?? '0') === '1',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_periods');
    }
};
