<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_team_id')->constrained('execution_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('member_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('role_name')->nullable();
            $table->text('task_description')->nullable();
            $table->boolean('task_completed')->nullable();
            $table->text('actual_task_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_team_members');
    }
};
