<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_report_id')->constrained('monitoring_reports')->cascadeOnDelete();
            $table->string('detail_type', 50)->nullable();
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->string('field_key');
            $table->string('field_label');
            $table->json('planned_value')->nullable();
            $table->json('actual_value')->nullable();
            $table->string('match_status', 30);
            $table->text('note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['monitoring_report_id', 'detail_type', 'detail_id'], 'field_verifications_detail_idx');
            $table->index('match_status', 'field_verifications_match_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_verifications');
    }
};
