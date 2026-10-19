<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Phase 4: every Monthly Activities KPI query is bounded by a proposed_date range (the KPI
 * period column), which had no index. Other KPI lookups are already covered by existing indexes
 * (see docs/kpi-reporting-refactor-phase4-module-branch-kpis.md, "Indexes").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->index('proposed_date', 'monthly_activities_proposed_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_activities', function (Blueprint $table) {
            $table->dropIndex('monthly_activities_proposed_date_idx');
        });
    }
};
