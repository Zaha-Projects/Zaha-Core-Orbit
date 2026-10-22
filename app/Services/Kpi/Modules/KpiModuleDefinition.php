<?php

namespace App\Services\Kpi\Modules;

use App\Services\Kpi\Support\KpiMetricSource;
use App\Services\Kpi\Support\KpiSql;
use App\Services\Kpi\Support\KpiWorkflowIdentity;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for how a module's records map onto the shared KPI lifecycle
 * (live record → planned → approved → executed → closed) and for the module's named metrics.
 *
 * Every fragment takes a table alias so the same definition can be reused inside correlated
 * subqueries (for example "has a live, executed Monthly activity"). Fragments contain no
 * bindings: they are built from constants, integer ids and Carbon dates only.
 *
 * Cumulative stages ("reached at least") are composed here so every module nests the same way:
 * closed ⊆ executed ⊆ approved ⊆ planned ⊆ live records.
 */
abstract class KpiModuleDefinition
{
    public const STAGES = ['planned', 'approved', 'executed', 'closed'];

    /** Arabic labels for workflow_action_logs.action_type values used in user reports. */
    protected const ACTION_LABELS = [
        'submitted' => 'تقديم للاعتماد',
        'post_execution_submitted' => 'إرسال بيانات ما بعد التنفيذ',
        'closed' => 'اعتماد ما بعد التنفيذ (إغلاق)',
        'execution_completed' => 'إكمال التنفيذ وإدخال الفعلي',
        'monitoring_approved' => 'اعتماد تقرير المتابعة',
        'monitoring_returned' => 'إعادة تقرير المتابعة للتصحيح',
        'iftar_closed' => 'إغلاق الإفطار',
        'monitoring_verification_submitted' => 'تقديم تقرير المتابعة',
    ];

    protected const DECISION_LABELS = ['approved' => 'اعتماد', 'changes_requested' => 'إعادة للتعديل', 'rejected' => 'رفض'];

    abstract public function key(): string;

    abstract public function table(): string;

    /** Unqualified column used to place a record inside a KPI period. */
    abstract protected function dateColumn(): string;

    /** Live/current-record condition (soft deletes, archives, superseded versions). */
    abstract public function liveSql(string $alias): string;

    /**
     * Raw module conditions per stage, or null when the module does not own that stage.
     *
     * @return array{planned: string, approved: string, executed: ?string, closed: ?string}
     */
    abstract protected function rawStageConditions(string $alias): array;

    /** Arabic reason shown for stages the module does not own. */
    public function unavailableReason(string $stage): ?string
    {
        return null;
    }

    /** Arabic coverage notes shown under the module's figures. */
    public function notes(): array
    {
        return [];
    }

    public function supportsStage(string $stage): bool
    {
        return ($this->rawStageConditions('s')[$stage] ?? null) !== null;
    }

    public function dateExpression(string $alias = 's'): string
    {
        return $alias.'.'.$this->dateColumn();
    }

    /** @return array<string, string> cumulative stage conditions; unsupported stages omitted */
    public function cumulativeStageSql(string $alias = 's'): array
    {
        $raw = $this->rawStageConditions($alias);
        $planned = '('.$raw['planned'].')';
        $any = static function (array $stages) use ($raw): string {
            $conditions = array_values(array_filter(array_intersect_key($raw, array_flip($stages))));

            return implode(' OR ', array_map(static fn (string $condition): string => '('.$condition.')', $conditions));
        };

        $sql = ['planned' => $planned, 'approved' => $planned.' AND ('.$any(['approved', 'executed', 'closed']).')'];
        if ($raw['executed'] !== null || $raw['closed'] !== null) {
            $sql['executed'] = $planned.' AND ('.$any(['executed', 'closed']).')';
        }
        if ($raw['closed'] !== null) {
            $sql['closed'] = $planned.' AND ('.$raw['closed'].')';
        }

        return $sql;
    }

    public function stageSql(string $stage, string $alias = 's'): ?string
    {
        return $this->cumulativeStageSql($alias)[$stage] ?? null;
    }

    /** Branch restriction for the module table: null = all branches, false never (branch_id exists). */
    public function branchScopeSql(string $alias, ?array $branchIds)
    {
        if ($branchIds === null) {
            return null;
        }

        return $branchIds === [] ? '1 = 0' : $alias.'.branch_id IN ('.KpiSql::intList($branchIds).')';
    }

    /** Prepare grouping by branch and return the branch key expression. */
    public function branchKey(Builder $query, string $alias): string
    {
        return $alias.'.branch_id';
    }

    /** Live records only; no period, no branch scope. */
    public function liveQuery(string $alias = 's'): Builder
    {
        return DB::table($this->table().' as '.$alias)->whereRaw($this->liveSql($alias));
    }

    /** @return array<int, KpiMetricSource> */
    public function metricSources(): array
    {
        return array_merge([$this->mainSource()], $this->secondarySources(), $this->actorSources());
    }

    public function metricSource(string $key): ?KpiMetricSource
    {
        foreach ($this->metricSources() as $source) {
            if ($source->key === $key) {
                return $source;
            }
        }

        return null;
    }

    /** Column holding the record's display name. */
    public function titleColumn(): string
    {
        return 'title';
    }

    /** Whether the module table has a branch_id column (Agenda does not). */
    public function hasBranchColumn(): bool
    {
        return true;
    }

    /** Names of every metric this module can provide (null SQL excluded). */
    public function metricNames(): array
    {
        $names = [];
        foreach ($this->metricSources() as $source) {
            foreach ($source->metrics as $name => $sql) {
                if ($sql !== null) {
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }

    protected function mainSource(): KpiMetricSource
    {
        return new KpiMetricSource(
            'main',
            fn (): Builder => $this->liveQuery('s'),
            $this->dateExpression('s'),
            fn (?array $branchIds) => $this->branchScopeSql('s', $branchIds),
            fn (Builder $query): string => $this->branchKey($query, 's'),
            array_merge($this->stageMetrics('s'), $this->mainMetrics('s'))
        );
    }

    /** Stage counts shared by every module, plus "due" variants (record date on or before today). */
    protected function stageMetrics(string $alias): array
    {
        $date = $this->dateExpression($alias);
        $today = KpiSql::literal(now()->toDateString());
        $metrics = ['records' => 'COUNT(*)'];

        foreach ($this->cumulativeStageSql($alias) as $stage => $condition) {
            $metrics[$stage] = KpiSql::countIf($condition);
        }
        $metrics['approved_due'] = KpiSql::countIf('('.$this->stageSql('approved', $alias).') AND '.$date.' <= '.$today);
        if ($this->supportsStage('executed')) {
            $metrics['executed_due'] = KpiSql::countIf('('.$this->stageSql('executed', $alias).') AND '.$date.' <= '.$today);
        }

        return $metrics;
    }

    /** Module-specific metrics computed on the main table (correlated subqueries allowed). */
    protected function mainMetrics(string $alias): array
    {
        return [];
    }

    /** @return array<int, KpiMetricSource> additional tables (requests, participations, …) */
    protected function secondarySources(): array
    {
        return [];
    }

    /**
     * Actor sources: one per user-attributable action (creation, submission, decision, …).
     * Each declares who acted (actor) and drill-down row columns.
     *
     * @return array<int, KpiMetricSource>
     */
    protected function actorSources(): array
    {
        return [];
    }

    /** Build an actor source whose subject table is aliased "s" (branch scope follows the subject). */
    protected function actorSource(string $key, Closure $base, string $date, string $actor, array $metrics, array $rows, array $rowFilters = []): KpiMetricSource
    {
        return new KpiMetricSource(
            $key,
            $base,
            $date,
            fn (?array $branchIds) => $this->branchScopeSql('s', $branchIds),
            fn (Builder $query): string => $this->branchKey($query, 's'),
            $metrics,
            $actor,
            ['event' => 's.'.$this->titleColumn(), 'action_date' => $date] + $rows,
            $rowFilters
        );
    }

    /** Actions recorded in workflow_action_logs (alias "l") joined to the subject (history: no live filter). */
    protected function actionLogSource(string $key, array $actions, array $metrics, array $rows = []): KpiMetricSource
    {
        $logModule = KpiWorkflowIdentity::actionLogModule($this->key());
        $types = KpiWorkflowIdentity::sqlList($this->key());

        return $this->actorSource(
            $key,
            fn (): Builder => DB::table('workflow_action_logs as l')
                ->join($this->table().' as s', 's.id', '=', 'l.entity_id')
                ->where('l.module', $logModule)
                ->whereRaw('l.entity_type IN ('.$types.')')
                ->whereIn('l.action_type', $actions),
            'l.performed_at',
            'l.performed_by',
            $metrics,
            $rows + ['action' => self::labelCase('l.action_type', self::ACTION_LABELS)]
        );
    }

    /** First time a workflow_action_logs action was recorded for the subject (correlated). */
    protected function firstActionLogSql(string $subjectId, string $action): string
    {
        return '(SELECT MIN(fl.performed_at) FROM workflow_action_logs fl WHERE fl.module = '.KpiSql::literal((string) KpiWorkflowIdentity::actionLogModule($this->key()))
            .' AND fl.entity_type IN ('.KpiWorkflowIdentity::sqlList($this->key()).') AND fl.entity_id = '.$subjectId.' AND fl.action_type = '.KpiSql::literal($action).')';
    }

    /**
     * Human decisions on main workflow steps (workflow_logs "wl"). Decision time runs from the
     * later of the previous log in the instance and the latest submission before the decision.
     *
     * @param  string|null  $firstSubmissionSql  correlated first-submission timestamp (for final-approval turnaround)
     */
    protected function decisionSource(?string $firstSubmissionSql, bool $usesSubmissionLogs): KpiMetricSource
    {
        $types = KpiWorkflowIdentity::sqlList($this->key());
        $previousLog = '(SELECT MAX(px.acted_at) FROM workflow_logs px WHERE px.workflow_instance_id = wl.workflow_instance_id'
            .' AND (px.acted_at < wl.acted_at OR (px.acted_at = wl.acted_at AND px.id < wl.id)))';
        $entry = $previousLog;
        if ($usesSubmissionLogs) {
            $latestSubmission = '(SELECT MAX(sx.performed_at) FROM workflow_action_logs sx WHERE sx.module = '.KpiSql::literal((string) KpiWorkflowIdentity::actionLogModule($this->key()))
                .' AND sx.entity_type IN ('.$types.") AND sx.entity_id = wi.entity_id AND sx.action_type = 'submitted' AND sx.performed_at <= wl.acted_at)";
            $entry = KpiSql::greatest($previousLog, $latestSubmission);
        }
        $decisionHours = KpiSql::hoursBetween($entry, 'wl.acted_at');
        $final = "wl.action = 'approved' AND wi.status = 'approved' AND NOT EXISTS (SELECT 1 FROM workflow_logs nx WHERE nx.workflow_instance_id = wl.workflow_instance_id"
            .' AND (nx.acted_at > wl.acted_at OR (nx.acted_at = wl.acted_at AND nx.id > wl.id)))';
        $finalHours = $firstSubmissionSql === null ? null : KpiSql::hoursBetween($firstSubmissionSql, 'wl.acted_at');

        return $this->actorSource(
            'u_decisions',
            fn (): Builder => DB::table('workflow_logs as wl')
                ->join('workflow_instances as wi', 'wi.id', '=', 'wl.workflow_instance_id')
                ->join('workflow_steps as st', 'st.id', '=', 'wl.workflow_step_id')
                ->join($this->table().' as s', 's.id', '=', 'wi.entity_id')
                ->whereRaw('wi.entity_type IN ('.$types.')')
                ->where('st.step_type', 'main')
                ->whereIn('wl.action', array_keys(self::DECISION_LABELS))
                ->whereRaw(KpiWorkflowIdentity::humanDecisionSql('wl')),
            'wl.acted_at',
            'wl.acted_by',
            [
                'u_decisions' => 'COUNT(*)',
                'u_decisions_approved' => KpiSql::countIf("wl.action = 'approved'"),
                'u_decisions_returned' => KpiSql::countIf("wl.action = 'changes_requested'"),
                'u_decisions_rejected' => KpiSql::countIf("wl.action = 'rejected'"),
                'u_reviewed_requests' => 'COUNT(DISTINCT wl.workflow_instance_id)',
                'u_decision_hours' => KpiSql::sumIf('TRUE', $decisionHours),
                'u_decision_measured' => KpiSql::countValue('TRUE', $decisionHours),
                'u_final_turnaround_hours' => $finalHours === null ? null : KpiSql::sumIf($final, $finalHours),
                'u_final_turnaround_measured' => $finalHours === null ? null : KpiSql::countValue($final, $finalHours),
            ],
            [
                'action' => 'COALESCE(st.name_ar, st.step_key)',
                'result' => self::labelCase('wl.action', self::DECISION_LABELS),
                'duration_hours' => $decisionHours,
            ],
            [
                'approved' => "wl.action = 'approved'",
                'returned' => "wl.action = 'changes_requested'",
                'rejected' => "wl.action = 'rejected'",
            ]
        );
    }

    /** CASE expression translating stored codes to Arabic labels. */
    protected static function labelCase(string $expression, array $labels): string
    {
        $sql = 'CASE '.$expression;
        foreach ($labels as $code => $label) {
            $sql .= ' WHEN '.KpiSql::literal($code).' THEN '.KpiSql::literal($label);
        }

        return $sql.' ELSE '.$expression.' END';
    }

    /** Correlated "workflow instance exists" condition using every accepted entity_type spelling. */
    protected function workflowInstanceExists(string $alias, string $typesSql, string $extra = ''): string
    {
        return 'EXISTS (SELECT 1 FROM workflow_instances wi WHERE wi.entity_id = '.$alias.'.id AND wi.entity_type IN ('.$typesSql.')'.$extra.')';
    }
}
