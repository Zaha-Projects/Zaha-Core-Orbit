<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_target_groups', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('target_group_id')->constrained('target_groups')->restrictOnDelete();
            $table->text('target_group_custom_text')->nullable();
            $table->foreignId('beneficiary_segment_id')->nullable()->constrained('beneficiary_segments')->restrictOnDelete();
            $table->text('segment_custom_text')->nullable();
            $table->unsignedInteger('planned_count');
            $table->unsignedInteger('actual_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'subject_target_groups_subject_idx');
            $table->index(
                ['subject_type', 'subject_id', 'target_group_id', 'beneficiary_segment_id'],
                'subject_target_groups_combination_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_target_groups');
    }
};
