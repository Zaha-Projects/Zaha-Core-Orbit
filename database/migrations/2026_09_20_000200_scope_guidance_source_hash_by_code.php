<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_guidance_versions', function (Blueprint $table): void {
            $table->dropUnique(['source_sha256']);
            $table->unique(['code', 'source_sha256'], 'event_guidance_code_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('event_guidance_versions', function (Blueprint $table): void {
            $table->dropUnique('event_guidance_code_source_unique');
            $table->unique('source_sha256');
        });
    }
};
