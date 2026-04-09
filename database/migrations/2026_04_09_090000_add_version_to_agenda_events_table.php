<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (! Schema::hasColumn('agenda_events', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('agenda_plan_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_events', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
