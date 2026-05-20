<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('available');
            $table->boolean('available')->default(false);
            $table->string('provider_type')->nullable();
            $table->string('provider_name')->nullable();
            $table->timestamps();

            $table->index('monthly_activity_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_supplies');
    }
};
