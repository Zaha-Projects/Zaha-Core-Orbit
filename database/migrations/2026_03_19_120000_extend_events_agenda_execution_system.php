<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->foreignId('event_type_id')->nullable()->after('target_group')->constrained('event_types')->nullOnDelete();
            $table->boolean('volunteers_required')->default(false)->after('needs_volunteers');
            $table->unsignedInteger('volunteers_count')->nullable()->after('volunteers_required');
            $table->decimal('attendance_rate', 8, 4)->nullable()->after('actual_attendance');
            $table->integer('attendance_gap')->nullable()->after('attendance_rate');
            $table->decimal('attendance_percentage', 5, 2)->nullable()->after('attendance_gap');
            $table->foreignId('correspondence_reason_id')->nullable()->after('needs_official_correspondence')->constrained('event_types')->nullOnDelete();
            $table->string('correspondence_status')->default('pending')->after('correspondence_reason_id');
            $table->string('building')->nullable()->after('internal_location');
            $table->string('room')->nullable()->after('building');
            $table->string('lifecycle_status')->default('Draft')->after('status');
        });

        Schema::create('workshops_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('event_id');
        });

        Schema::create('communications_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->json('media_files')->nullable();
            $table->timestamps();
            $table->unique('event_id');
        });

        Schema::create('correspondence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_target_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->foreignId('target_group_id')->constrained('target_groups')->cascadeOnDelete();
            $table->string('custom_text')->nullable();
            $table->timestamps();
            $table->unique(['monthly_activity_id', 'target_group_id']);
        });

        Schema::table('monthly_activity_partners', function (Blueprint $table) {
            $table->string('contact_info')->nullable()->after('role');
            $table->unique(['monthly_activity_id', 'name']);
        });

        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->unique(['monthly_activity_id', 'member_email']);
        });

        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->string('status')->default('available')->after('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->dropUnique(['monthly_activity_id', 'member_email']);
        });
        Schema::table('monthly_activity_partners', function (Blueprint $table) {
            $table->dropUnique(['monthly_activity_id', 'name']);
            $table->dropColumn('contact_info');
        });
        Schema::dropIfExists('event_target_group');
        Schema::dropIfExists('correspondence_logs');
        Schema::dropIfExists('communications_requests');
        Schema::dropIfExists('workshops_requests');

        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_type_id');
            $table->dropConstrainedForeignId('correspondence_reason_id');
            $table->dropColumn([
                'volunteers_required',
                'volunteers_count',
                'attendance_rate',
                'attendance_gap',
                'attendance_percentage',
                'correspondence_status',
                'building',
                'room',
                'lifecycle_status',
            ]);
        });

        Schema::dropIfExists('event_types');
    }
};
