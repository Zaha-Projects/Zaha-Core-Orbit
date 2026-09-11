<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_execution_needs', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('execution_need_type_id')->constrained('execution_need_types')->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->text('planned_details')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('actual_details')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'execution_need_type_id'], 'subject_execution_needs_subject_type_unique');
            $table->index(['subject_type', 'subject_id'], 'subject_execution_needs_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_execution_needs');
    }
};
