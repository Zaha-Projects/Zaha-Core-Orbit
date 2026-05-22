<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('official_correspondences', function (Blueprint $table) {
            $table->id();
            $table->morphs('correspondable');
            $table->string('reason')->nullable();
            $table->string('target')->nullable();
            $table->text('brief')->nullable();
            $table->timestamps();
            $table->unique(['correspondable_type', 'correspondable_id'], 'official_corr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_correspondences');
    }
};

