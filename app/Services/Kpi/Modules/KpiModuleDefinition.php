<?php

namespace App\Services\Kpi\Modules;

use App\Services\Kpi\Support\KpiMetricSource;
use App\Services\Kpi\Support\KpiSql;
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
        return array_merge([$this->mainSource()], $this->secondarySources());
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

    /** Correlated "workflow instance exists" condition using every accepted entity_type spelling. */
    protected function workflowInstanceExists(string $alias, string $typesSql, string $extra = ''): string
    {
        return 'EXISTS (SELECT 1 FROM workflow_instances wi WHERE wi.entity_id = '.$alias.'.id AND wi.entity_type IN ('.$typesSql.')'.$extra.')';
    }
}
