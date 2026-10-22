<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_reports', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('monitoring_method_id')->constrained('monitoring_methods')->restrictOnDelete();
            $table->foreignId('monitor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('observed_at')->nullable();
            $table->text('general_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'monitoring_reports_subject_idx');
            $table->index(['subject_type', 'subject_id', 'status'], 'monitoring_reports_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_reports');
    }
};
