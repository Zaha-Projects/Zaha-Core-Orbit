<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_id')->constrained('ramadan_iftars')->cascadeOnDelete();
            $table->text('description');
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('source_name')->nullable();
            $table->string('restaurant_name')->nullable();
            $table->string('restaurant_contact', 50)->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->unsignedSmallInteger('rating')->nullable();
            $table->text('rating_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_meals');
    }
};
