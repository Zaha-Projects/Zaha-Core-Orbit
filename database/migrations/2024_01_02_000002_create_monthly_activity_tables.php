<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('monthly_activities')) {
            Schema::create('monthly_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('month');
                $table->unsignedTinyInteger('day');
                $table->string('title');
                $table->date('proposed_date');
                $table->date('modified_proposed_date')->nullable();
                $table->date('actual_date')->nullable();
                $table->boolean('is_in_agenda')->default(false);
                $table->foreignId('agenda_event_id')->nullable()->constrained('agenda_events')->cascadeOnUpdate()->nullOnDelete();
                $table->text('description')->nullable();
                $table->boolean('has_official_attendance')->default(false);
                $table->text('official_attendance_details')->nullable();
                $table->boolean('needs_official_letters')->default(false);
                $table->string('location_type')->default('in_center');
                $table->text('location_details')->nullable();
                $table->time('time_from')->nullable();
                $table->time('time_to')->nullable();
                $table->string('media_coverage')->nullable();
                $table->string('status')->default('draft');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnUpdate()->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('monthly_activity_supplies')) {
            Schema::create('monthly_activity_supplies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('item_name');
                $table->boolean('available')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('monthly_activity_team')) {
            Schema::create('monthly_activity_team', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->string('member_name');
                $table->string('role_desc')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('monthly_activity_attachments')) {
            Schema::create('monthly_activity_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('file_type');
                $table->string('file_path');
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('monthly_activity_approvals')) {
            Schema::create('monthly_activity_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('step');
                $table->string('decision');
                $table->text('comment')->nullable();
                $table->foreignId('approved_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activity_attendance')) {
            Schema::create('activity_attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnUpdate()->cascadeOnDelete();
                $table->unsignedInteger('expected_count')->nullable();
                $table->unsignedInteger('actual_count')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_attendance');
        Schema::dropIfExists('monthly_activity_approvals');
        Schema::dropIfExists('monthly_activity_attachments');
        Schema::dropIfExists('monthly_activity_team');
        Schema::dropIfExists('monthly_activity_supplies');
        Schema::dropIfExists('monthly_activities');
    }
};
