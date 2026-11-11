<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movement Phase 3: create movement_vehicle_types (docs/movement-module-implementation-plan.md §7.1, Revision 4).
 *
 * Category 2 (creates an operational table, §18.0):
 * - up() creates the table.
 * - down() REFUSES to run while the table has rows (RuntimeException naming the table). Rolling back
 *   is allowed only while it is empty, e.g. after a failed deploy; once records exist, fix forward.
 */
return new class extends Migration {
    private const TABLE = 'movement_vehicle_types';

    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique('movement_vehicle_types_code_unique');
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('size_class'); // small | medium | large (D6)
            $table->unsignedSmallInteger('default_capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
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
