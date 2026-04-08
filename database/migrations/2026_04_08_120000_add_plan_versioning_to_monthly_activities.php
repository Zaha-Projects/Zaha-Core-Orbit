<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->unsignedInteger('plan_stage')->default(1)->after('status');
            $table->unsignedInteger('plan_version')->default(1)->after('plan_stage');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropColumn(['plan_stage', 'plan_version']);
        });
    }
};
