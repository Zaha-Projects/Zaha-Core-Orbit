<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('maintenance_requests')) {
            return;
        }

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamp('logged_at');
            $table->string('type');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
