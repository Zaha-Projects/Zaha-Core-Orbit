<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 3: create movement_locations (docs/movement-module-implementation-plan.md §7.5, Revision 4).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - branch_id is metadata only ("this place is a branch site"), nullOnDelete (§7.5). No branch isolation (R5).
 */
return new class extends Migration {
    private const TABLE = 'movement_locations';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('google_place_id')->nullable();
            $table->text('maps_url')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
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
