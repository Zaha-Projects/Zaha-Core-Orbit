<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('target_groups', fn (Blueprint $table) => $table->boolean('is_bazaar')->default(true)->after('is_ramadan_iftar'));

        DB::table('execution_need_types')->orderBy('id')->get(['id', 'module_config', 'is_monthly_activity'])->each(function ($need): void {
            $config = json_decode($need->module_config ?: '{}', true) ?: [];
            $config['bazaar'] ??= ['available' => (bool) $need->is_monthly_activity, 'required' => false];
            DB::table('execution_need_types')->where('id', $need->id)->update(['module_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        });
    }

    public function down(): void
    {
        DB::table('execution_need_types')->orderBy('id')->get(['id', 'module_config'])->each(function ($need): void {
            $config = json_decode($need->module_config ?: '{}', true) ?: [];
            unset($config['bazaar']);
            DB::table('execution_need_types')->where('id', $need->id)->update(['module_config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        });
        Schema::table('target_groups', fn (Blueprint $table) => $table->dropColumn('is_bazaar'));
    }
};
