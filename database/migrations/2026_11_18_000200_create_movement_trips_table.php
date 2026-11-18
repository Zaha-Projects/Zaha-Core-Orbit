<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 5 (release set 5A): create movement_trips (docs/movement-module-implementation-plan.md §7.8, Revision 6).
 * Runs after 2026_11_18_000100, which drops legacy C's table of the same name.
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table with the full §7.8 schema; later phases add no columns (Phases 6–10: "DB: none").
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - FKs follow D19: vehicle_id / driver_id restrict (catalogues soft-delete); actor columns null on delete;
 *   the self references (paired / merged) null on delete. No branch column (R5).
 * - seat_capacity is nullable until the manager sets it or the first vehicle assignment fills it (Revision 6).
 */
return new class extends Migration {
    private const TABLE = 'movement_trips';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable()->unique('movement_trips_reference_unique'); // MT-{year}-{id}, set right after insert
            $table->string('status');
            $table->foreignId('vehicle_id')->nullable()->constrained('movement_vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('movement_drivers')->restrictOnDelete();
            $table->unsignedSmallInteger('seat_capacity')->nullable();
            $table->dateTime('planned_departure_at')->nullable(); // UTC, first stop
            $table->dateTime('planned_arrival_at')->nullable(); // UTC, last stop
            $table->unsignedSmallInteger('pre_trip_buffer_minutes');
            $table->unsignedSmallInteger('post_trip_buffer_minutes');
            $table->dateTime('blocked_from')->nullable(); // planned_departure_at − pre
            $table->dateTime('blocked_until')->nullable(); // planned_arrival_at + post
            $table->foreignId('paired_trip_id')->nullable()->constrained('movement_trips')->nullOnDelete();
            $table->boolean('vehicle_waits_between')->default(false);
            $table->boolean('driver_waits_between')->default(false);
            $table->boolean('allows_join')->default(true);
            $table->dateTime('booking_closes_at')->nullable();
            $table->unsignedInteger('estimated_distance_m')->nullable();
            $table->unsignedInteger('estimated_duration_s')->nullable();
            $table->string('route_source')->default('manual'); // manual | routes_api
            $table->foreignId('merged_into_trip_id')->nullable()->constrained('movement_trips')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('actual_departure_at')->nullable();
            $table->dateTime('actual_arrival_at')->nullable();
            $table->dateTime('driver_acknowledged_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vehicle_id', 'blocked_from', 'blocked_until'], 'movement_trips_vehicle_window_idx');
            $table->index(['driver_id', 'blocked_from', 'blocked_until'], 'movement_trips_driver_window_idx');
            $table->index(['status', 'planned_departure_at'], 'movement_trips_status_departure_idx');
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
