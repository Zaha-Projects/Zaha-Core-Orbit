<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_key')->unique();
            $table->string('name');
            $table->string('role_name')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('icon', 32)->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_units');
    }
};
