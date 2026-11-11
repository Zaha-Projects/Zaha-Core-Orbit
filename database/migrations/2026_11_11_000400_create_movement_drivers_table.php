<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 3: create movement_drivers (docs/movement-module-implementation-plan.md §7.3, Revision 4).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 * - No branch_id (R5). user_id is the account link (§13.3), nullOnDelete. Unique columns include
 *   soft-deleted rows (Revision 4).
 */
return new class extends Migration {
    private const TABLE = 'movement_drivers';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique('movement_drivers_user_unique')->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('licence_number')->nullable()->unique('movement_drivers_licence_number_unique');
            $table->string('licence_category')->nullable();
            $table->date('licence_expires_on')->nullable();
            $table->string('employment_status');
            $table->unsignedBigInteger('legacy_source_id')->nullable()->unique('movement_drivers_legacy_source_unique');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('employment_status', 'movement_drivers_status_idx');
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
