<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_supplies', function (Blueprint $table) {
            $table->boolean('planned_available')->nullable()->after('planned_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('event_supplies', fn (Blueprint $table) => $table->dropColumn('planned_available'));
    }
};
