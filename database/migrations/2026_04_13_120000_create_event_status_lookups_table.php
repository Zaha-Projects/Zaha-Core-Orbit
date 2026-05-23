<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_status_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50);
            $table->string('code', 100);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module', 'code'], 'evt_status_mod_code_uniq');
            $table->index(['module', 'is_active'], 'evt_status_mod_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_status_lookups');
    }
};
