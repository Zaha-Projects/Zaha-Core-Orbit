<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('monthly_activity_supplies')) {
            return;
        }

        Schema::create('monthly_activity_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->boolean('available')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_supplies');
    }
};
