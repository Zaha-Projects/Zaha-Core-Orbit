<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('monthly_activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->text('note');
            $table->string('coverage_status')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notes');
    }
};
