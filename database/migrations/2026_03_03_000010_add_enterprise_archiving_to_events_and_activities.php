<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('status');
            $table->unsignedSmallInteger('archived_year')->nullable()->after('is_archived');
            $table->index(['is_archived', 'archived_year']);
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('status');
            $table->unsignedSmallInteger('archived_year')->nullable()->after('is_archived');
            $table->index(['is_archived', 'archived_year']);
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropIndex(['is_archived', 'archived_year']);
            $table->dropColumn(['is_archived', 'archived_year']);
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropIndex(['is_archived', 'archived_year']);
            $table->dropColumn(['is_archived', 'archived_year']);
        });
    }
};
