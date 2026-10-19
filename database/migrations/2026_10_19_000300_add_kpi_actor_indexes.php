<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Phase 6: indexes for the user-performance (actor) queries introduced in Phase 5.
 *
 * - workflow_action_logs(module, action_type, performed_at): every action-log metric source
 *   filters module + action_type and bounds performed_at to the KPI period (submissions,
 *   post-execution, closures, monitoring). The existing index (module, entity_type, entity_id)
 *   serves the correlated per-entity lookups, not period scans.
 * - workflow_logs(acted_by, acted_at): decision sources filter acted_by IN (users) and bound
 *   acted_at to the period; workflow_logs had no index on acted_at.
 *
 * Additive only; historical migrations are untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_action_logs', function (Blueprint $table) {
            $table->index(['module', 'action_type', 'performed_at'], 'workflow_action_logs_kpi_action_idx');
        });
        Schema::table('workflow_logs', function (Blueprint $table) {
            $table->index(['acted_by', 'acted_at'], 'workflow_logs_actor_acted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_action_logs', function (Blueprint $table) {
            $table->dropIndex('workflow_action_logs_kpi_action_idx');
        });
        Schema::table('workflow_logs', function (Blueprint $table) {
            $table->dropIndex('workflow_logs_actor_acted_at_idx');
        });
    }
};
