<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 1: retire the never-used legacy A fleet-trip subsystem
 * (docs/movement-module-implementation-plan.md §18.2, confirmed requirement R2).
 *
 * Category 4 (destructive legacy retirement, §18.0):
 * - up() drops trip_rounds, trip_segments and trips (children first). Tables that are already
 *   absent are skipped, so the migration is idempotent across environments.
 * - up() refuses to drop a table that still contains rows unless
 *   config('movement.legacy.allow_drop_with_rows') is true (env MOVEMENT_ALLOW_LEGACY_DROP),
 *   the explicit "disaster-recovery backup verified" switch.
 * - down() recreates the EMPTY structure exactly as defined by the original 2024_02_01_0122/0123/0124
 *   migrations, so a code rollback to the previous release finds the schema it expects.
 *   It never restores data: rows are recoverable only from the disaster-recovery backup.
 *   This is not an archive and no application code reads these tables any more.
 *
 * Only legacy A is touched. vehicles, drivers, transport_request* and movement_days/movement_trips
 * are retired in later phases. Historical migrations are untouched.
 */
return new class extends Migration {
    /** Child tables first, so foreign keys to trips are gone before trips itself. */
    private const TABLES = ['trip_rounds', 'trip_segments', 'trips'];

    public function up(): void
    {
        $allowDropWithRows = (bool) config('movement.legacy.allow_drop_with_rows', false);

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! $allowDropWithRows && DB::table($table)->exists()) {
                throw new RuntimeException(sprintf(
                    'Refusing to drop legacy table [%s]: it contains rows. Verify the disaster-recovery backup, '
                    .'then set MOVEMENT_ALLOW_LEGACY_DROP=true for this deploy (see docs/movement-module-implementation-plan.md §18.1).',
                    $table
                ));
            }
        }

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->id();
                $table->date('trip_date');
                $table->string('day_name')->nullable();
                $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('scheduled');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index('trip_date', 'trips_trip_date_idx');
                $table->index('status', 'trips_status_idx');
            });
        }

        if (! Schema::hasTable('trip_segments')) {
            Schema::create('trip_segments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('segment_no');
                $table->string('location');
                $table->string('team_companion')->nullable();
                $table->time('depart_time')->nullable();
                $table->time('return_time')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('segment_no', 'trip_segments_no_idx');
            });
        }

        if (! Schema::hasTable('trip_rounds')) {
            Schema::create('trip_rounds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('round_no');
                $table->string('location');
                $table->string('team')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('round_no', 'trip_rounds_no_idx');
            });
        }
    }
};
