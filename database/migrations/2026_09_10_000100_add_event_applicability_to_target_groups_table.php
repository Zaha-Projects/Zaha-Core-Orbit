<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('target_groups', function (Blueprint $table) {
            $table->boolean('is_monthly_activity')->default(true)->after('is_active');
            $table->boolean('is_ramadan_iftar')->default(true)->after('is_monthly_activity');
            $table->index('is_monthly_activity', 'target_groups_monthly_idx');
            $table->index('is_ramadan_iftar', 'target_groups_ramadan_idx');
        });
    }

    public function down(): void
    {
        Schema::table('target_groups', function (Blueprint $table) {
            $table->dropIndex('target_groups_monthly_idx');
            $table->dropIndex('target_groups_ramadan_idx');
            $table->dropColumn(['is_monthly_activity', 'is_ramadan_iftar']);
        });
    }
};
