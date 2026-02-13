<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transport_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('request_date');
            $table->string('day_name')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('movement_officer_notes')->nullable();
            $table->text('general_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_request_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('trip_no')->default(1);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('destination');
            $table->string('accompanying_team')->nullable();
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_request_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('action_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_request_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('punctuality_score')->nullable();
            $table->unsignedTinyInteger('cleanliness_score')->nullable();
            $table->unsignedTinyInteger('driver_behavior_score')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_request_feedback');
        Schema::dropIfExists('transport_request_actions');
        Schema::dropIfExists('transport_request_trips');
        Schema::dropIfExists('transport_requests');
    }
};

