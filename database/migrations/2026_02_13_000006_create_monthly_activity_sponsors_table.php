<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_activity_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->boolean('is_official')->default(true);
            $table->timestamps();$table->index('is_official','ma_sponsors_official_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_activity_sponsors');
    }
};

