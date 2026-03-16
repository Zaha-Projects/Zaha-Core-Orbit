<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->string('internal_location')->nullable()->after('location_details');
            $table->string('outside_place_name')->nullable()->after('internal_location');
            $table->string('outside_google_maps_url')->nullable()->after('outside_place_name');
            $table->text('outside_address')->nullable()->after('outside_google_maps_url');

            $table->foreignId('target_group_id')->nullable()->after('target_group')->constrained('target_groups')->nullOnDelete();
            $table->string('target_group_other')->nullable()->after('target_group_id');

            $table->boolean('needs_volunteers')->default(false)->after('volunteer_need');
            $table->unsignedInteger('required_volunteers')->nullable()->after('needs_volunteers');

            $table->unsignedInteger('expected_attendance')->nullable()->after('required_volunteers');
            $table->unsignedInteger('actual_attendance')->nullable()->after('expected_attendance');
            $table->text('attendance_notes')->nullable()->after('actual_attendance');

            $table->boolean('needs_media_coverage')->default(false)->after('media_coverage');
            $table->text('media_coverage_notes')->nullable()->after('needs_media_coverage');

            $table->boolean('needs_official_correspondence')->default(false)->after('needs_official_letters');
            $table->string('official_correspondence_reason')->nullable()->after('needs_official_correspondence');

            $table->unsignedTinyInteger('work_teams_count')->nullable()->after('short_description');
        });

        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->string('team_name')->nullable()->after('monthly_activity_id');
            $table->string('member_email')->nullable()->after('member_name');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->dropColumn(['team_name', 'member_email']);
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_group_id');
            $table->dropColumn([
                'internal_location',
                'outside_place_name',
                'outside_google_maps_url',
                'outside_address',
                'target_group_other',
                'needs_volunteers',
                'required_volunteers',
                'expected_attendance',
                'actual_attendance',
                'attendance_notes',
                'needs_media_coverage',
                'media_coverage_notes',
                'needs_official_correspondence',
                'official_correspondence_reason',
                'work_teams_count',
            ]);
        });
    }
};
