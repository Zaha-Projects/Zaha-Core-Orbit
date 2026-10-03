<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bazaars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('relations_officer_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->date('bazaar_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->enum('location_type', ['inside', 'outside']);
            $table->string('location_name');
            $table->text('location_details')->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->unsignedInteger('planned_table_count');
            $table->unsignedInteger('actual_occupied_table_count')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('execution_started_at')->nullable();
            $table->timestamp('post_execution_submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaars');
    }
};
