<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execution_need_types', function (Blueprint $table) {
            $table->boolean('mandatory_for_monthly')->default(false)->after('is_ramadan_iftar');
            $table->boolean('mandatory_for_ramadan')->default(false)->after('mandatory_for_monthly');
        });

        Schema::table('ramadan_iftar_gifts', function (Blueprint $table) {
            $table->string('gift_type', 30)->default('gifts')->after('ramadan_iftar_id');
        });

        Schema::create('event_guidance_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('event_guidance_version_id');
            $table->timestamp('acknowledged_at');
            $table->timestamps();
            $table->foreign('user_id', 'ega_user_fk')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_guidance_version_id', 'ega_guidance_fk')
                ->references('id')->on('event_guidance_versions')->onDelete('cascade');
            $table->unique(['user_id', 'event_guidance_version_id'], 'ega_user_guidance_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guidance_acknowledgements');
        Schema::table('ramadan_iftar_gifts', fn (Blueprint $table) => $table->dropColumn('gift_type'));
        Schema::table('execution_need_types', fn (Blueprint $table) => $table->dropColumn(['mandatory_for_monthly', 'mandatory_for_ramadan']));
    }
};
