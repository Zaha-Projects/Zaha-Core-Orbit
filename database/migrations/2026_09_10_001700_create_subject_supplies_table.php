<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_supplies', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->string('item_name');
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->boolean('is_available')->nullable();
            $table->string('provider_type')->nullable();
            $table->string('provider_name')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id'], 'subject_supplies_subject_idx');
            $table->index(['subject_type', 'subject_id', 'status'], 'subject_supplies_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_supplies');
    }
};
