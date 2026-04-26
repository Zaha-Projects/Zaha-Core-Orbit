<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (! Schema::hasColumn('agenda_events', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_archived');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_events', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
