<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 5 (release set 5A): create movement_trip_stops (docs/movement-module-implementation-plan.md §7.9).
 *
 * Category 2 (creates an operational table, §18.0): down() refuses while the table has rows.
 * - Stops keep snapshot columns (name, address, coordinates, Maps link), so editing a saved location never rewrites history.
 * - (movement_trip_id, sequence) is unique. Stops are never deleted and recreated wholesale; bookings reference
 *   stop ids with restrict (§7.10), so a stop with bookings cannot be removed.
 * - location_id is restrict (saved locations soft-delete, D19).
 */
return new class extends Migration {
    private const TABLE = 'movement_trip_stops';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_trip_id')->constrained('movement_trips')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('location_id')->nullable()->constrained('movement_locations')->restrictOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('maps_url')->nullable();
            $table->dateTime('planned_arrival_at')->nullable(); // UTC; NULL for the first stop
            $table->dateTime('planned_departure_at')->nullable(); // UTC; NULL for the last stop
            $table->unsignedInteger('running_seconds_from_previous')->nullable(); // free-flow, §9.4
            $table->unsignedInteger('distance_m_from_previous')->nullable();
            $table->boolean('allows_pickup')->default(true);
            $table->boolean('allows_drop_off')->default(true);
            $table->dateTime('actual_arrival_at')->nullable();
            $table->dateTime('actual_departure_at')->nullable();
            $table->timestamps();
            $table->unique(['movement_trip_id', 'sequence'], 'movement_trip_stops_sequence_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE) && DB::table(self::TABLE)->exists()) {
            throw new RuntimeException(sprintf(
                'Refusing to drop [%s]: it contains rows. Category 2 migrations roll back only while empty; fix forward instead (docs/movement-module-implementation-plan.md §18.0).',
                self::TABLE
            ));
        }

        Schema::dropIfExists(self::TABLE);
    }
};
