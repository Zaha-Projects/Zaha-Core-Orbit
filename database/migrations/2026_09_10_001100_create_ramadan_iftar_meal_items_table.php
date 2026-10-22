<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_iftar_meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ramadan_iftar_meal_id')->constrained('ramadan_iftar_meals')->cascadeOnDelete();
            $table->string('name');
            $table->string('item_type', 30);
            $table->unsignedInteger('quantity')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['ramadan_iftar_meal_id', 'sort_order'], 'ramadan_meal_items_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_iftar_meal_items');
    }
};
