<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('planned_activities_count')->default(0);
            $table->unsignedInteger('unplanned_activities_count')->default(0);
            $table->unsignedTinyInteger('modification_rate_percent')->nullable();
            $table->unsignedTinyInteger('plan_commitment_percent')->nullable();
            $table->unsignedTinyInteger('mobilization_efficiency_percent')->nullable();
            $table->unsignedTinyInteger('branch_monthly_score')->nullable();
            $table->unsignedTinyInteger('followup_commitment_score')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month', 'branch_id', 'center_id'], 'monthly_kpis_unique_period_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_kpis');
    }
};
