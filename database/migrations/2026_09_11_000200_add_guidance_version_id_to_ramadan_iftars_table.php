<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ramadan_iftars', function (Blueprint $table) {
            $table->foreignId('guidance_version_id')
                ->nullable()
                ->after('parent_version_id')
                ->constrained('event_guidance_versions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ramadan_iftars', function (Blueprint $table) {
            $table->dropForeign(['guidance_version_id']);
            $table->dropColumn('guidance_version_id');
        });
    }
};
