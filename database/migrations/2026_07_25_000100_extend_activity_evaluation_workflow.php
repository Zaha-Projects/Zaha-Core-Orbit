<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->foreignId('evaluation_form_id')->nullable()->after('id')->constrained('evaluation_forms')->nullOnDelete();
            $table->string('question_ar')->nullable()->after('question');
            $table->string('question_en')->nullable()->after('question_ar');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('minimum_score', 5, 2)->default(1);
            $table->decimal('maximum_score', 5, 2)->default(10);
            $table->decimal('weight', 8, 3)->default(1);
            $table->boolean('is_required')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['evaluation_form_id', 'is_active', 'sort_order'], 'evaluation_question_form_active_order_idx');
        });

        Schema::create('post_execution_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('field_key');
            $table->string('field_label')->nullable();
            $table->string('value_type', 30)->default('text');
            $table->json('original_value')->nullable();
            $table->json('corrected_value')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['monthly_activity_id', 'field_key'], 'post_execution_verification_field_unique');
            $table->index(['branch_id', 'status'], 'post_execution_verification_branch_status_idx');
        });

        Schema::create('activity_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('evaluation_form_id')->constrained('evaluation_forms')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('weighted_points', 12, 4);
            $table->decimal('weight_total', 12, 4);
            $table->decimal('normalized_score', 5, 2);
            $table->string('visibility', 30)->default('branch_only');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('visibility_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('visibility_updated_at')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'submitted_at'], 'activity_evaluation_branch_date_idx');
            $table->index(['visibility', 'normalized_score'], 'activity_evaluation_visibility_score_idx');
        });

        Schema::create('activity_evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_question_id')->nullable()->constrained('evaluation_questions')->nullOnDelete();
            $table->text('question_ar');
            $table->text('question_en');
            $table->decimal('minimum_score', 5, 2);
            $table->decimal('maximum_score', 5, 2);
            $table->decimal('weight', 8, 3);
            $table->decimal('score', 5, 2);
            $table->decimal('weighted_score', 12, 4);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['activity_evaluation_id', 'evaluation_question_id'], 'activity_evaluation_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_evaluation_answers');
        Schema::dropIfExists('activity_evaluations');
        Schema::dropIfExists('post_execution_verifications');
        Schema::table('evaluation_questions', function (Blueprint $table) {
            $table->dropForeign(['evaluation_form_id']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex('evaluation_question_form_active_order_idx');
            $table->dropColumn(['evaluation_form_id', 'question_ar', 'question_en', 'description_ar', 'description_en', 'minimum_score', 'maximum_score', 'weight', 'is_required', 'updated_by']);
        });
        Schema::dropIfExists('evaluation_forms');
    }
};
