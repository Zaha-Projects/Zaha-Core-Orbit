<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 5 (release set 5A): retire legacy C, the driver daily movements
 * (docs/movement-module-implementation-plan.md §18.3, Revision 6).
 *
 * Dated BEFORE create_movement_trips_table: legacy C owns a table called movement_trips, and the new
 * module needs that name (§18.3, §20 "name collision").
 *
 * Category 4 (destructive legacy retirement, §18.0):
 * - up() drops the legacy movement_trips and then movement_days (child first). Absent tables are skipped, so
 *   the migration is idempotent. A movement_trips table without the legacy `movement_day_id` column is NOT
 *   legacy C and is never dropped here.
 * - up() refuses to drop a table that still contains rows unless config('movement.legacy.allow_drop_with_rows')
 *   is true (env MOVEMENT_ALLOW_LEGACY_DROP), the explicit "disaster-recovery backup verified" switch (§18.1).
 *   No C data is migrated or archived (R3).
 * - down() recreates the EMPTY structure exactly as defined by 2026_03_09_000012/000013. It never restores data:
 *   rows are recoverable only from the disaster-recovery backup. It refuses while the new movement_trips exists
 *   (roll back create_movement_trips_table first, which happens automatically in a normal rollback).
 */
return new class extends Migration {
    /** Child table first, so the FK to movement_days is gone before it. */
    private const TABLES = ['movement_trips', 'movement_days'];

    public function up(): void
    {
        $allowDropWithRows = (bool) config('movement.legacy.allow_drop_with_rows', false);
        $tables = array_values(array_filter(self::TABLES, fn (string $table) => $this->isLegacy($table)));

        // Check every table before dropping any, so a refusal leaves the schema untouched.
        foreach ($tables as $table) {
            if (! $allowDropWithRows && DB::table($table)->exists()) {
                throw new RuntimeException(sprintf(
                    'Refusing to drop legacy table [%s]: it contains rows. Verify the disaster-recovery backup, '
                    .'then set MOVEMENT_ALLOW_LEGACY_DROP=true for this deploy (see docs/movement-module-implementation-plan.md §18.1).',
                    $table
                ));
            }
        }

        foreach ($tables as $table) {
            Schema::drop($table);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movement_trips') && ! Schema::hasColumn('movement_trips', 'movement_day_id')) {
            throw new RuntimeException('Refusing to recreate legacy C: the new movement_trips table still exists. Roll back 2026_11_18_000200_create_movement_trips_table first.');
        }

        // Parents first; each block mirrors its original migration verbatim.
        if (! Schema::hasTable('movement_days')) {
            Schema::create('movement_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->constrained('drivers');
                $table->date('date')->index();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['driver_id', 'date']);
            });
        }

        if (! Schema::hasTable('movement_trips')) {
            Schema::create('movement_trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movement_day_id')->constrained('movement_days')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->string('destination');
                $table->string('team')->nullable();
                $table->time('departure_time')->nullable();
                $table->time('return_time')->nullable();
                $table->timestamps();
                $table->index('movement_day_id', 'movement_trips_day_idx');
            });
        }
    }

    /** movement_days is always legacy; movement_trips only while it still has C's movement_day_id column. */
    private function isLegacy(string $table): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return $table !== 'movement_trips' || Schema::hasColumn('movement_trips', 'movement_day_id');
    }
};
