<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 4 (release set 4A): create movement_request_legs (docs/movement-module-implementation-plan.md §7.7, Revision 5).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - movement_request_id cascades (the parent is never hard-deleted); saved locations restrict (they soft-delete).
 * - One leg per type per request: unique (movement_request_id, leg_type).
 */
return new class extends Migration {
    private const TABLE = 'movement_request_legs';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_request_id')->constrained('movement_requests')->cascadeOnDelete();
            $table->string('leg_type'); // outbound | return
            $table->foreignId('origin_location_id')->nullable()->constrained('movement_locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('movement_locations')->restrictOnDelete();
            $table->string('origin_text')->nullable();
            $table->string('destination_text')->nullable();
            $table->text('origin_maps_url')->nullable();
            $table->text('destination_maps_url')->nullable();
            $table->unsignedSmallInteger('passenger_count');
            $table->date('travel_date'); // local date of required_arrival_at (Revision 5)
            $table->dateTime('required_arrival_at'); // UTC
            $table->dateTime('preferred_departure_at')->nullable(); // UTC
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['movement_request_id', 'leg_type'], 'movement_request_legs_request_type_unique');
            $table->index(['travel_date', 'required_arrival_at'], 'movement_request_legs_date_idx');
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
