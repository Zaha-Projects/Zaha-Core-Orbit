<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color_hex', 7)->nullable();
            $table->string('icon', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name', 'departments_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
