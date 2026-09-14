<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_segments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('dimension', 30);
            $table->unsignedSmallInteger('minimum_age')->nullable();
            $table->unsignedSmallInteger('maximum_age')->nullable();
            $table->boolean('is_other')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'beneficiary_segments_active_order_idx');
            $table->index('dimension', 'beneficiary_segments_dimension_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_segments');
    }
};
