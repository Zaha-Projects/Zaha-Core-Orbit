<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->boolean('is_mandatory')->default(false)->after('event_type');
            $table->boolean('is_unified')->default(true)->after('is_mandatory');
            $table->string('agenda_plan_file')->nullable()->after('notes');
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->boolean('is_from_agenda')->default(false)->after('is_in_agenda');
            $table->string('participation_status')->nullable()->after('status');
            $table->string('plan_type')->nullable()->after('participation_status');
            $table->string('branch_plan_file')->nullable()->after('plan_type');
            $table->boolean('is_program_related')->default(false)->after('requires_programs');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'is_from_agenda',
                'participation_status',
                'plan_type',
                'branch_plan_file',
                'is_program_related',
            ]);
        });

        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropColumn([
                'is_mandatory',
                'is_unified',
                'agenda_plan_file',
            ]);
        });
    }
};
