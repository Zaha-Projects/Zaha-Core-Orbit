<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 3: create movement_vehicles (docs/movement-module-implementation-plan.md §7.2, Revision 4).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - No branch_id (R5). vehicle_type_id and passenger_capacity are nullable only for imported rows
 *   still pending verification (Revision 4). Unique columns include soft-deleted rows (Revision 4).
 */
return new class extends Migration {
    private const TABLE = 'movement_vehicles';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('fleet_number')->unique('movement_vehicles_fleet_number_unique');
            $table->string('plate_number')->nullable()->unique('movement_vehicles_plate_number_unique');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('movement_vehicle_types')->restrictOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->nullable();
            $table->string('operational_status');
            $table->string('status_reason')->nullable();
            $table->dateTime('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('home_location_id')->nullable()->constrained('movement_locations')->nullOnDelete();
            $table->unsignedBigInteger('legacy_source_id')->nullable()->unique('movement_vehicles_legacy_source_unique');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('operational_status', 'movement_vehicles_status_idx');
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
