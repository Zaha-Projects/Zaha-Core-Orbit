<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'monitoring_methods_active_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_methods');
    }
};
