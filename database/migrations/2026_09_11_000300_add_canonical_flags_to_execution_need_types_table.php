<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execution_need_types', function (Blueprint $table) {
            $table->boolean('is_canonical')->default(false)->after('is_active');
            $table->boolean('is_monthly_activity')->default(false)->after('is_canonical');
            $table->boolean('is_ramadan_iftar')->default(false)->after('is_monthly_activity');
            $table->index(['is_canonical', 'is_active'], 'execution_need_types_canonical_idx');
            $table->index('is_ramadan_iftar', 'execution_need_types_ramadan_idx');
        });
    }

    public function down(): void
    {
        Schema::table('execution_need_types', function (Blueprint $table) {
            $table->dropIndex('execution_need_types_canonical_idx');
            $table->dropIndex('execution_need_types_ramadan_idx');
            $table->dropColumn(['is_canonical', 'is_monthly_activity', 'is_ramadan_iftar']);
        });
    }
};
