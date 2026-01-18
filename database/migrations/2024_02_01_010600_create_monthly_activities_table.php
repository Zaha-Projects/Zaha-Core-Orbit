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
            $table->date('actual_date')->nullable();
            $table->boolean('is_in_agenda')->default(false);
            $table->foreignId('agenda_event_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('has_official_attendance')->default(false);
            $table->text('official_attendance_details')->nullable();
            $table->boolean('needs_official_letters')->default(false);
            $table->string('location_type');
            $table->string('location_details')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->string('media_coverage')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activities');
    }
};
