<?php

namespace App\Services\Kpi\Support;

use Closure;
use Illuminate\Database\Query\Builder;

/**
 * One aggregate-able table (or join) feeding named KPI metrics for a module.
 *
 * - base: returns the live, unscoped query.
 * - scope: returns null (no restriction), a SQL condition, or false when the source cannot
 *   be scoped to branches (its metrics are then undefined for that viewer).
 * - branchKey: prepares grouping by branch and returns the branch key expression.
 */
class KpiMetricSource
{
    public string $key;
    public string $dateExpression;
    /** @var array<string, string|null> metric name => aggregate SQL (null = not computable here) */
    public array $metrics;

    private Closure $base;
    private Closure $scope;
    private Closure $branchKey;

    public function __construct(string $key, Closure $base, string $dateExpression, Closure $scope, Closure $branchKey, array $metrics)
    {
        $this->key = $key;
        $this->base = $base;
        $this->dateExpression = $dateExpression;
        $this->scope = $scope;
        $this->branchKey = $branchKey;
        $this->metrics = $metrics;
    }

    /**
     * @return array{0: Builder, 1: ?string}|null null when the source is unavailable in this scope
     */
    public function build(?array $branchIds, bool $groupByBranch): ?array
    {
        $condition = ($this->scope)($branchIds);
        if ($condition === false) {
            return null;
        }

        /** @var Builder $query */
        $query = ($this->base)();
        if (is_string($condition)) {
            $query->whereRaw($condition);
        }

        return [$query, $groupByBranch ? ($this->branchKey)($query) : null];
    }

    public function isAvailableFor(?array $branchIds): bool
    {
        return ($this->scope)($branchIds) !== false;
    }
}
