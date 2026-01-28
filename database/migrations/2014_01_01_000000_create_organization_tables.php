<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('centers')) {
            Schema::create('centers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('centers');
        Schema::dropIfExists('branches');
    }
};
