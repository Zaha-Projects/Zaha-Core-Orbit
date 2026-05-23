<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_volunteer_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('volunteer_need')->nullable();
            $table->unsignedInteger('required_volunteers')->nullable();
            $table->string('volunteer_age_range')->nullable();
            $table->string('volunteer_gender')->nullable();
            $table->text('volunteer_tasks_summary')->nullable();
            $table->boolean('volunteers_required')->default(false);
            $table->unsignedInteger('volunteers_count')->nullable();
            $table->timestamps();

            $table->unique('monthly_activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_volunteer_needs');
    }
};
