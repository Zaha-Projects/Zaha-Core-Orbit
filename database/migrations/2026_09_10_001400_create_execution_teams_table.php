<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_teams', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->string('name');
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('planned_members_count')->nullable();
            $table->unsignedInteger('actual_members_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'execution_teams_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_teams');
    }
};
