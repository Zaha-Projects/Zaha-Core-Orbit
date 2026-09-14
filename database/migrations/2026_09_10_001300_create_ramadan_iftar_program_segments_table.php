<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_program_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_id')->constrained('ramadan_iftars')->cascadeOnDelete();
            $table->string('name');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('executor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_executor_name')->nullable();
            $table->string('execution_status', 30)->default('planned');
            $table->text('actual_notes')->nullable();
            $table->timestamps();

            $table->index(['ramadan_iftar_id', 'sort_order'], 'ramadan_program_segments_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_program_segments');
    }
};
