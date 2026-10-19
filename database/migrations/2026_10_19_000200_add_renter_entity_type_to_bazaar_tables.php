<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bazaar_tables', function (Blueprint $table) {
            $table->string('renter_entity_type', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bazaar_tables', fn (Blueprint $table) => $table->dropColumn('renter_entity_type'));
    }
};
