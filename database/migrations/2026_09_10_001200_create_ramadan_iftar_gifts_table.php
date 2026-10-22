<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_id')->constrained('ramadan_iftars')->cascadeOnDelete();
            $table->text('description');
            $table->unsignedInteger('planned_quantity');
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->boolean('has_supporting_entity')->default(false);
            $table->string('supporting_entity_name')->nullable();
            $table->decimal('unit_value', 12, 2)->nullable();
            $table->decimal('estimated_total_value', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_gifts');
    }
};
