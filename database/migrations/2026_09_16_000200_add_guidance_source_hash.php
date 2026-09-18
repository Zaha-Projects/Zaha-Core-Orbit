<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_guidance_versions', function (Blueprint $table) {
            $table->string('source_sha256', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('event_guidance_versions', function (Blueprint $table) {
            $table->dropUnique(['source_sha256']);
            $table->dropColumn('source_sha256');
        });
    }
};
