<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 3: create movement_unavailabilities (docs/movement-module-implementation-plan.md §7.4, Revision 4).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - Exactly one of vehicle_id / driver_id is set: enforced by the Form Request and the model. No CHECK
 *   constraint, because the production MySQL version is unverified (Revision 4).
 */
return new class extends Migration {
    private const TABLE = 'movement_unavailabilities';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('movement_vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('movement_drivers')->restrictOnDelete();
            $table->string('type'); // maintenance | leave | inspection | other
            $table->dateTime('starts_at'); // UTC
            $table->dateTime('ends_at'); // UTC
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vehicle_id', 'starts_at', 'ends_at'], 'movement_unavail_vehicle_window_idx');
            $table->index(['driver_id', 'starts_at', 'ends_at'], 'movement_unavail_driver_window_idx');
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
