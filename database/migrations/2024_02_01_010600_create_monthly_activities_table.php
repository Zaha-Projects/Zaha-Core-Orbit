<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->string('title');
            $table->date('proposed_date');
            $table->date('modified_proposed_date')->nullable();
            $table->date('rescheduled_date')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->boolean('relations_approval_on_reschedule')->default(false);
            $table->date('actual_date')->nullable();
            $table->boolean('is_in_agenda')->default(false);
            $table->boolean('is_from_agenda')->default(false);
            $table->foreignId('agenda_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('responsible_party')->nullable();
            $table->text('description')->nullable();
            $table->boolean('has_sponsor')->default(false);
            $table->string('sponsor_name_title')->nullable();
            $table->boolean('has_partners')->default(false);
            $table->string('partner_1_name')->nullable();
            $table->string('partner_1_role')->nullable();
            $table->string('partner_2_name')->nullable();
            $table->string('partner_2_role')->nullable();
            $table->string('partner_3_name')->nullable();
            $table->string('partner_3_role')->nullable();
            $table->boolean('has_official_attendance')->default(false);
            $table->text('official_attendance_details')->nullable();
            $table->boolean('needs_official_letters')->default(false);
            $table->string('letter_purpose')->nullable();
            $table->string('location_type');
            $table->string('location_details')->nullable();
            $table->string('internal_location')->nullable();
            $table->string('building')->nullable();
            $table->string('room')->nullable();
            $table->string('outside_place_name')->nullable();
            $table->string('outside_google_maps_url')->nullable();
            $table->text('outside_address')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('execution_time')->nullable();
            $table->string('target_group')->nullable();
            $table->unsignedBigInteger('target_group_id')->nullable();
            $table->unsignedBigInteger('event_type_id')->nullable();
            $table->string('target_group_other')->nullable();
            $table->text('short_description')->nullable();
            $table->string('volunteer_need')->nullable();
            $table->boolean('needs_volunteers')->default(false);
            $table->unsignedInteger('required_volunteers')->nullable();
            $table->boolean('volunteers_required')->default(false);
            $table->unsignedInteger('volunteers_count')->nullable();
            $table->unsignedTinyInteger('work_teams_count')->nullable();
            $table->unsignedInteger('expected_attendance')->nullable();
            $table->unsignedInteger('actual_attendance')->nullable();
            $table->text('attendance_notes')->nullable();
            $table->decimal('attendance_rate', 8, 4)->nullable();
            $table->integer('attendance_gap')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->decimal('audience_satisfaction_percent', 5, 2)->nullable();
            $table->decimal('evaluation_score', 5, 2)->nullable();
            $table->string('media_coverage')->nullable();
            $table->boolean('needs_media_coverage')->default(false);
            $table->text('media_coverage_notes')->nullable();
            $table->boolean('requires_programs')->default(false);
            $table->boolean('requires_workshops')->default(false);
            $table->boolean('requires_communications')->default(false);
            $table->boolean('is_program_related')->default(false);
            $table->boolean('needs_official_correspondence')->default(false);
            $table->unsignedBigInteger('correspondence_reason_id')->nullable();
            $table->string('official_correspondence_reason')->nullable();
            $table->string('correspondence_status')->default('pending');
            $table->string('status')->default('draft');
            $table->string('participation_status')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('branch_plan_file')->nullable();
            $table->string('lifecycle_status')->default('Draft');
            $table->string('relations_officer_approval_status')->default('pending');
            $table->string('relations_manager_approval_status')->default('pending');
            $table->string('programs_officer_approval_status')->default('pending');
            $table->string('programs_manager_approval_status')->default('pending');
            $table->string('liaison_approval_status')->default('pending');
            $table->string('hq_relations_manager_approval_status')->default('pending');
            $table->string('executive_approval_status')->default('pending');
            $table->timestamp('lock_at')->nullable();
            $table->boolean('is_official')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->unsignedSmallInteger('archived_year')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_archived', 'archived_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activities');
    }
};
