<?php

namespace App\Services\Kpi\Support;

use Closure;
use Illuminate\Database\Query\Builder;

/**
 * One aggregate-able table (or join) feeding named KPI metrics for a module.
 *
 * - base: returns the unscoped query (live filter and joins included).
 * - scope: returns null (no restriction), a SQL condition, or false when the source cannot
 *   be scoped to branches (its metrics are then undefined for that viewer).
 * - branchKey: prepares grouping by branch and returns the branch key expression.
 * - actor: SQL expression of the user who performed the action (actor sources only). Only
 *   sources with an actor take part in user grouping or user-filtered datasets.
 * - rows: drill-down row columns (alias => SQL) shared by the HTML report and the CSV export;
 *   rowFilters: named extra conditions (e.g. "returned") for row reports.
 */
class KpiMetricSource
{
    public string $key;
    public string $dateExpression;
    /** @var array<string, string|null> metric name => aggregate SQL (null = not computable here) */
    public array $metrics;
    public ?string $actor;
    /** @var array<string, string> */
    public array $rows;
    /** @var array<string, string> */
    public array $rowFilters;

    private Closure $base;
    private Closure $scope;
    private Closure $branchKey;

    public function __construct(string $key, Closure $base, string $dateExpression, Closure $scope, Closure $branchKey, array $metrics, ?string $actor = null, array $rows = [], array $rowFilters = [])
    {
        $this->key = $key;
        $this->base = $base;
        $this->dateExpression = $dateExpression;
        $this->scope = $scope;
        $this->branchKey = $branchKey;
        $this->metrics = $metrics;
        $this->actor = $actor;
        $this->rows = $rows;
        $this->rowFilters = $rowFilters;
    }

    /**
     * @param  array<int, int>|null  $userIds  restrict to actions performed by these users (actor sources only)
     * @return array{0: Builder, 1: ?string}|null null when the source is unavailable in this scope
     */
    public function build(?array $branchIds, bool $groupByBranch, ?array $userIds = null): ?array
    {
        $condition = ($this->scope)($branchIds);
        if ($condition === false || ($userIds !== null && $this->actor === null)) {
            return null;
        }

        /** @var Builder $query */
        $query = ($this->base)();
        if (is_string($condition)) {
            $query->whereRaw($condition);
        }
        if ($userIds !== null) {
            $query->whereRaw($userIds === [] ? '1 = 0' : $this->actor.' IN ('.KpiSql::intList($userIds).')');
        }

        return [$query, $groupByBranch ? ($this->branchKey)($query) : null];
    }

    public function isAvailableFor(?array $branchIds): bool
    {
        return ($this->scope)($branchIds) !== false;
    }
}
