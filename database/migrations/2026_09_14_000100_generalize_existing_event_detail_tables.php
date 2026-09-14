<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setNullable('event_target_group', 'monthly_activity_id', 'BIGINT UNSIGNED');
        Schema::table('event_target_group', function (Blueprint $table) {
            $table->string('subject_type', 100)->nullable()->after('id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->text('target_group_custom_text')->nullable()->after('custom_text');
            $table->foreignId('beneficiary_segment_id')->nullable()->constrained('beneficiary_segments')->restrictOnDelete();
            $table->text('segment_custom_text')->nullable();
            $table->unsignedInteger('planned_count')->nullable();
            $table->unsignedInteger('actual_count')->nullable();
            $table->text('notes')->nullable();
            $table->index(['subject_type', 'subject_id'], 'event_target_group_subject_idx');
        });

        DB::table('event_target_group')->whereNotNull('monthly_activity_id')->update([
            'subject_type' => 'monthly_activity',
            'subject_id' => DB::raw('monthly_activity_id'),
            'target_group_custom_text' => DB::raw('custom_text'),
        ]);

        $this->setNullable('monthly_activity_team', 'monthly_activity_id', 'BIGINT UNSIGNED');
        $this->setNullable('monthly_activity_team', 'member_name', 'VARCHAR(255)');
        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->foreignId('execution_team_id')->nullable()->after('monthly_activity_id')->constrained('execution_teams')->cascadeOnDelete();
            $table->string('phone', 50)->nullable();
            $table->string('role_name')->nullable();
            $table->text('task_description')->nullable();
            $table->boolean('task_completed')->nullable();
            $table->text('actual_task_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->index('execution_team_id', 'monthly_activity_team_execution_team_idx');
        });

        $this->setNullable('monthly_activity_supplies', 'monthly_activity_id', 'BIGINT UNSIGNED');
        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->string('subject_type', 50)->nullable()->after('id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->unsignedInteger('planned_quantity')->nullable();
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->boolean('is_available')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->index(['subject_type', 'subject_id'], 'monthly_activity_supplies_subject_idx');
        });

        DB::table('monthly_activity_supplies')->whereNotNull('monthly_activity_id')->update([
            'subject_type' => 'monthly_activity',
            'subject_id' => DB::raw('monthly_activity_id'),
            'planned_quantity' => DB::raw('quantity'),
            'is_available' => DB::raw('available'),
        ]);

        $this->setNullable('post_execution_verifications', 'monthly_activity_id', 'BIGINT UNSIGNED');
        $this->setNullable('post_execution_verifications', 'branch_id', 'BIGINT UNSIGNED');
        Schema::table('post_execution_verifications', function (Blueprint $table) {
            $table->foreignId('monitoring_report_id')->nullable()->after('id')->constrained('monitoring_reports')->cascadeOnDelete();
            $table->string('detail_type', 50)->nullable();
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->json('planned_value')->nullable();
            $table->json('actual_value')->nullable();
            $table->string('match_status', 30)->nullable();
            $table->index(['monitoring_report_id', 'detail_type', 'detail_id'], 'post_execution_verifications_detail_idx');
            $table->index('match_status', 'post_execution_verifications_match_idx');
        });
    }

    public function down(): void
    {
        Schema::table('post_execution_verifications', function (Blueprint $table) {
            $table->dropForeign(['monitoring_report_id']);
            $table->dropIndex('post_execution_verifications_detail_idx');
            $table->dropIndex('post_execution_verifications_match_idx');
            $table->dropColumn(['monitoring_report_id', 'detail_type', 'detail_id', 'planned_value', 'actual_value', 'match_status']);
        });
        $this->setNotNullable('post_execution_verifications', 'monthly_activity_id', 'BIGINT UNSIGNED');
        $this->setNotNullable('post_execution_verifications', 'branch_id', 'BIGINT UNSIGNED');

        Schema::table('monthly_activity_supplies', function (Blueprint $table) {
            $table->dropIndex('monthly_activity_supplies_subject_idx');
            $table->dropColumn(['subject_type', 'subject_id', 'planned_quantity', 'actual_quantity', 'is_available', 'estimated_value', 'notes']);
        });
        $this->setNotNullable('monthly_activity_supplies', 'monthly_activity_id', 'BIGINT UNSIGNED');

        Schema::table('monthly_activity_team', function (Blueprint $table) {
            $table->dropForeign(['execution_team_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropIndex('monthly_activity_team_execution_team_idx');
            $table->dropColumn(['execution_team_id', 'phone', 'role_name', 'task_description', 'task_completed', 'actual_task_note', 'confirmed_by', 'confirmed_at']);
        });
        $this->setNotNullable('monthly_activity_team', 'monthly_activity_id', 'BIGINT UNSIGNED');
        $this->setNotNullable('monthly_activity_team', 'member_name', 'VARCHAR(255)');

        Schema::table('event_target_group', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_segment_id']);
            $table->dropIndex('event_target_group_subject_idx');
            $table->dropColumn(['subject_type', 'subject_id', 'target_group_custom_text', 'beneficiary_segment_id', 'segment_custom_text', 'planned_count', 'actual_count', 'notes']);
        });
        $this->setNotNullable('event_target_group', 'monthly_activity_id', 'BIGINT UNSIGNED');
    }

    private function setNullable(string $table, string $column, string $sqlType): void
    {
        $this->setNullability($table, $column, $sqlType, true);
    }

    private function setNotNullable(string $table, string $column, string $sqlType): void
    {
        $this->setNullability($table, $column, $sqlType, false);
    }

    private function setNullability(string $table, string $column, string $sqlType, bool $nullable): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s %s',
                $table,
                $column,
                $sqlType,
                $nullable ? 'NULL' : 'NOT NULL'
            ));

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE "%s" ALTER COLUMN "%s" %s NOT NULL',
                $table,
                $column,
                $nullable ? 'DROP' : 'SET'
            ));

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $sqlType, $nullable) {
            $definition = strpos($sqlType, 'VARCHAR') === 0
                ? $blueprint->string($column)
                : $blueprint->unsignedBigInteger($column);
            $definition->nullable($nullable)->change();
        });
    }
};
