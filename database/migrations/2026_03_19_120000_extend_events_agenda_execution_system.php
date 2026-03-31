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
    }

    public function down(): void
    {
        Schema::dropIfExists('event_target_group');
        Schema::dropIfExists('correspondence_logs');
        Schema::dropIfExists('communications_requests');
        Schema::dropIfExists('workshops_requests');
        Schema::dropIfExists('event_types');
    }
};
