<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->string('execution_status')->default('executed')->after('status');
            $table->text('cancellation_reason')->nullable()->after('reschedule_reason');
            $table->string('external_liaison_name')->nullable()->after('outside_contact_number');
            $table->string('external_liaison_phone')->nullable()->after('external_liaison_name');
            $table->string('volunteer_age_range')->nullable()->after('required_volunteers');
            $table->string('volunteer_gender')->nullable()->after('volunteer_age_range');
            $table->text('volunteer_tasks_summary')->nullable()->after('volunteer_gender');
            $table->text('official_correspondence_brief')->nullable()->after('official_correspondence_target');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn([
                'execution_status',
                'cancellation_reason',
                'external_liaison_name',
                'external_liaison_phone',
                'volunteer_age_range',
                'volunteer_gender',
                'volunteer_tasks_summary',
                'official_correspondence_brief',
            ]);
        });
    }
};
