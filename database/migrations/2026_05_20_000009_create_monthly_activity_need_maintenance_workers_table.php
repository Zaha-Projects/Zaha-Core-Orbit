<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_need_maintenance_workers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_activity_id');
            $table->foreign('monthly_activity_id', 'fk_man_000009_ma')->references('id')->on('monthly_activities')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->json('payload')->nullable();
            $table->json('followup')->nullable();
            $table->json('post_execution')->nullable();
            $table->timestamps();

            $table->unique('monthly_activity_id', 'uq_man_000009_ma');
            $table->index('monthly_activity_id', 'idx_man_000009_ma');
            $table->index('is_required', 'idx_man_000009_req');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_need_maintenance_workers');
    }
};
