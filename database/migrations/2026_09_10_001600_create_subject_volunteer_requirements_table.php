<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_volunteer_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('beneficiary_segment_id')->nullable()->constrained('beneficiary_segments')->nullOnDelete();
            $table->string('gender', 30)->nullable();
            $table->unsignedInteger('planned_count');
            $table->unsignedInteger('actual_count')->nullable();
            $table->text('tasks_summary')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'volunteer_requirements_subject_idx');
            $table->index(['subject_type', 'subject_id', 'status'], 'volunteer_requirements_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_volunteer_requirements');
    }
};
