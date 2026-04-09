<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (! Schema::hasColumn('agenda_events', 'owner_department_id')) {
                $table->foreignId('owner_department_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('departments')
                    ->nullOnDelete();
            }

            $table->index('owner_department_id');
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('monthly_activities', 'activity_date')) {
                $table->date('activity_date')->nullable()->after('day');
            }

            $table->index('plan_version');
            $table->index('previous_version_id');
            $table->index('activity_date');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_activities', 'activity_date')) {
                $table->dropIndex(['activity_date']);
                $table->dropColumn('activity_date');
            }

            $table->dropIndex(['plan_version']);
            $table->dropIndex(['previous_version_id']);
        });

        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropIndex(['owner_department_id']);

            if (Schema::hasColumn('agenda_events', 'owner_department_id')) {
                $table->dropConstrainedForeignId('owner_department_id');
            }
        });
    }
};
