<?php

namespace App\Services\Kpi;

use App\Services\Kpi\Modules\KpiModuleDefinition;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Calculates catalogue KPIs from the module lifecycle definitions.
 *
 * Query budget per module: one aggregate query covering the current and comparison period
 * (contiguous range, grouped by period), one grouped query for the 12-month trend, and one
 * grouped query for the branch table. Nothing is loaded row by row.
 */
class KpiCalculatorService
{
    /** Derived stages and the lifecycle stage that must be supported for them. */
    private const DERIVED_STAGES = ['approved_due' => 'approved', 'executed_due' => 'executed'];

    private const BUCKETS = [
        'draft' => 'مسودة',
        'awaiting' => 'قيد الاعتماد / معاد',
        'approved_open' => 'معتمد بانتظار التنفيذ',
        'executed_open' => 'منفذ بانتظار الإغلاق',
        'closed' => 'مغلق',
    ];

    private const NO_BRANCHES_REASON = 'لا توجد فروع ضمن نطاق صلاحياتك؛ لا يمكن عرض بيانات.';

    private KpiRegistry $registry;
    private KpiTargetResolver $targets;
    private KpiFormatter $formatter;

    public function __construct(KpiRegistry $registry, KpiTargetResolver $targets, KpiFormatter $formatter)
    {
        $this->registry = $registry;
        $this->targets = $targets;
        $this->formatter = $formatter;
    }

    /**
     * Everything the overview / module pages need for one period and scope.
     *
     * @param  array<int, string>  $moduleKeys
     */
    public function overview(array $moduleKeys, KpiPeriod $period, KpiScope $scope, ?string $definitionModule = null): array
    {
        $totals = $this->stageTotals($moduleKeys, $period, $scope);
        $results = [];
        foreach ($this->registry->definitions($definitionModule) as $code => $definition) {
            $results[$code] = $this->calculate($definition, $moduleKeys, $totals, $period, $scope);
        }

        return [
            'results' => $results,
            'modules' => $this->moduleSummaries($moduleKeys, $totals, $scope),
            'charts' => [
                'eventsByModule' => $this->eventsByModuleChart($moduleKeys, $totals),
                'statusDistribution' => $this->statusDistributionChart($moduleKeys, $totals),
                'monthlyTrend' => $this->monthlyTrendChart($moduleKeys, $period, $scope),
            ],
        ];
    }

    /** Module summary cards only (no KPI cards, no charts). */
    public function summaries(array $moduleKeys, KpiPeriod $period, KpiScope $scope): array
    {
        return $this->moduleSummaries($moduleKeys, $this->stageTotals($moduleKeys, $period, $scope), $scope);
    }

    /**
     * Stage counts per branch for the current period.
     *
     * @return array<int, array{branch_id: int, branch: string, modules: array<string, array<string, int|null>>}>
     */
    public function branchBreakdown(array $moduleKeys, KpiPeriod $period, KpiScope $scope): array
    {
        if ($scope->hasNoBranches()) {
            return [];
        }

        $branches = $scope->availableBranches();
        if ($scope->selectedBranchId() !== null) {
            $branches = $branches->where('id', $scope->selectedBranchId());
        }

        $counts = [];
        foreach ($moduleKeys as $key) {
            $definition = $this->registry->moduleDefinition($key);
            $query = $definition->liveQuery();
            $branchKey = $definition->joinBranchKey($query, $scope->branchIds());
            $query->whereBetween($definition->dateExpression(), [$period->startDate(), $period->endDate()]);

            $selects = [$branchKey.' AS branch_key'];
            foreach ($definition->cumulativeStageSql() as $stage => $sql) {
                $selects[] = 'SUM(CASE WHEN '.$sql.' THEN 1 ELSE 0 END) AS '.$stage;
            }

            foreach ($query->selectRaw(implode(', ', $selects))->groupBy('branch_key')->get() as $row) {
                $counts[$key][(int) $row->branch_key] = (array) $row;
            }
        }

        return $branches->map(function ($branch) use ($moduleKeys, $counts): array {
            $modules = [];
            foreach ($moduleKeys as $key) {
                $definition = $this->registry->moduleDefinition($key);
                foreach (KpiModuleDefinition::STAGES as $stage) {
                    $modules[$key][$stage] = $definition->supportsStage($stage)
                        ? (int) ($counts[$key][(int) $branch->id][$stage] ?? 0)
                        : null;
                }
            }

            return ['branch_id' => (int) $branch->id, 'branch' => $branch->name, 'modules' => $modules];
        })->values()->all();
    }

    /**
     * @return array<string, array{current: ?array, previous: ?array}>  null = no data in scope
     */
    public function stageTotals(array $moduleKeys, KpiPeriod $period, KpiScope $scope): array
    {
        $totals = [];
        foreach ($moduleKeys as $key) {
            $totals[$key] = $scope->hasNoBranches()
                ? ['current' => null, 'previous' => null]
                : $this->moduleTotals($this->registry->moduleDefinition($key), $period, $scope);
        }

        return $totals;
    }

    private function moduleTotals(KpiModuleDefinition $definition, KpiPeriod $period, KpiScope $scope): array
    {
        $previous = $period->previous();
        $date = $definition->dateExpression();
        $today = now()->toDateString();
        $stageSql = $definition->cumulativeStageSql();

        $periodCase = 'CASE WHEN '.$date." >= ? THEN 'current' ELSE 'previous' END";
        $selects = [$periodCase.' AS period_key', 'COUNT(*) AS records'];
        $bindings = [$period->startDate()];
        foreach ($stageSql as $stage => $sql) {
            $selects[] = 'SUM(CASE WHEN '.$sql.' THEN 1 ELSE 0 END) AS '.$stage;
        }
        foreach (self::DERIVED_STAGES as $derived => $base) {
            if (isset($stageSql[$base])) {
                $selects[] = 'SUM(CASE WHEN ('.$stageSql[$base].') AND '.$date.' <= ? THEN 1 ELSE 0 END) AS '.$derived;
                $bindings[] = $today;
            }
        }

        $query = $definition->liveQuery();
        $definition->applyBranchScope($query, $scope->branchIds());
        $rows = $query
            ->whereBetween($date, [$previous->startDate(), $period->endDate()])
            ->selectRaw(implode(', ', $selects), $bindings)
            ->groupBy('period_key')
            ->get()
            ->keyBy('period_key');

        $shape = function ($row) use ($definition): array {
            $values = ['records' => (int) ($row->records ?? 0)];
            foreach (array_merge(KpiModuleDefinition::STAGES, array_keys(self::DERIVED_STAGES)) as $stage) {
                $base = self::DERIVED_STAGES[$stage] ?? $stage;
                $values[$stage] = $definition->supportsStage($base) ? (int) ($row->{$stage} ?? 0) : null;
            }

            return $values;
        };

        return ['current' => $shape($rows->get('current')), 'previous' => $shape($rows->get('previous'))];
    }

    private function calculate(array $definition, array $moduleKeys, array $totals, KpiPeriod $period, KpiScope $scope): KpiResult
    {
        $calculator = $definition['calculator'];
        $unit = $definition['unit'];
        $applicable = array_values(array_intersect($moduleKeys, $definition['applicable_modules']));
        $requiredStages = $calculator['type'] === 'stage_rate'
            ? [$calculator['numerator'], $calculator['denominator']]
            : [$calculator['stage']];

        $included = array_values(array_filter($applicable, function (string $key) use ($requiredStages): bool {
            foreach ($requiredStages as $stage) {
                if (! $this->registry->moduleDefinition($key)->supportsStage(self::DERIVED_STAGES[$stage] ?? $stage)) {
                    return false;
                }
            }

            return true;
        }));

        $current = $this->compute($calculator, $included, $totals, 'current');
        $previous = $this->compute($calculator, $included, $totals, 'previous');

        $missingReason = null;
        if ($scope->hasNoBranches()) {
            $missingReason = self::NO_BRANCHES_REASON;
        } elseif ($included === []) {
            $first = $moduleKeys[0] ?? null;
            $missingReason = ($first ? $this->registry->moduleDefinition($first)->unavailableReason($requiredStages[0]) : null)
                ?? 'لا تتوفر بيانات لهذا المؤشر ضمن الوحدات المختارة.';
        } elseif ($current['value'] === null) {
            $missingReason = 'لا توجد سجلات كافية لحساب النسبة ضمن الفترة.';
        }

        $target = $this->targets->resolve($definition);
        $achieved = $this->targets->achieved($current['value'] === null ? null : (float) $current['value'], $target);

        return new KpiResult([
            'code' => $definition['code'],
            'name' => $definition['name_ar'],
            'description' => $definition['description_ar'],
            'icon' => $definition['icon'] ?? 'fa-chart-simple',
            'unit' => $unit,
            'value' => $current['value'],
            'formattedValue' => $this->formatter->format($current['value'], $unit),
            'numerator' => $current['numerator'],
            'denominator' => $current['denominator'],
            'target' => $target['target'],
            'formattedTarget' => $this->formattedTarget($target, $unit),
            'targetSource' => $target['source'],
            'configurationRequired' => $target['configuration_required'],
            'achieved' => $achieved,
            'state' => $this->state($current['value'], $target, $achieved),
            'period' => $period->label(),
            'comparisonPeriod' => $period->previous()->label(),
            'trend' => $this->trend($current, $previous, $unit, $calculator['type']),
            'dataAvailable' => $current['value'] !== null,
            'missingReason' => $missingReason,
            'notes' => $this->exclusionNotes($included, $moduleKeys),
            'breakdown' => $this->breakdown($calculator, $moduleKeys, $definition, $totals, $requiredStages),
            'drilldown' => $definition['drilldown'] ?? null,
        ]);
    }

    /**
     * @return array{value: int|float|null, numerator: int|null, denominator: int|null, records: int}
     */
    private function compute(array $calculator, array $included, array $totals, string $periodKey): array
    {
        $sum = static function (string $stage) use ($included, $totals, $periodKey): ?int {
            $total = 0;
            foreach ($included as $key) {
                if ($totals[$key][$periodKey] === null) {
                    return null;
                }
                $total += (int) $totals[$key][$periodKey][$stage];
            }

            return $total;
        };

        $records = 0;
        foreach ($included as $key) {
            $records += (int) ($totals[$key][$periodKey]['records'] ?? 0);
        }

        if ($included === []) {
            return ['value' => null, 'numerator' => null, 'denominator' => null, 'records' => 0];
        }

        if ($calculator['type'] === 'stage_rate') {
            $numerator = $sum($calculator['numerator']);
            $denominator = $sum($calculator['denominator']);
            $value = ($numerator !== null && $denominator) ? round($numerator / $denominator * 100, 1) : null;

            return ['value' => $value, 'numerator' => $numerator, 'denominator' => $denominator, 'records' => $records];
        }

        $value = $sum($calculator['stage']);

        return ['value' => $value, 'numerator' => $value, 'denominator' => null, 'records' => $records];
    }

    /** Trend only when both periods hold comparable data (records, or a non-zero denominator for rates). */
    private function trend(array $current, array $previous, string $unit, string $type): ?array
    {
        if ($current['value'] === null || $previous['value'] === null) {
            return null;
        }
        if ($type === 'stage_count' && ($current['records'] === 0 || $previous['records'] === 0)) {
            return null;
        }

        $delta = (float) $current['value'] - (float) $previous['value'];

        return [
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
            'delta' => $delta,
            'formatted' => $this->formatter->formatDelta($delta, $unit),
            'previous' => $this->formatter->format($previous['value'], $unit),
        ];
    }

    private function state($value, array $target, ?bool $achieved): string
    {
        if ($value === null) {
            return KpiResult::STATE_INSUFFICIENT;
        }
        if ($target['operator'] === null) {
            return KpiResult::STATE_INFORMATIONAL;
        }
        if ($achieved === null) {
            return KpiResult::STATE_NO_TARGET;
        }

        return $achieved ? KpiResult::STATE_ACHIEVED : KpiResult::STATE_NOT_ACHIEVED;
    }

    private function formattedTarget(array $target, string $unit): ?string
    {
        if ($target['target'] === null) {
            return $target['configuration_required'] ? 'لم يتم تحديد المستهدف' : null;
        }

        $text = $target['operator'].' '.$this->formatter->format($target['target'], $unit);

        return $target['source'] === KpiTargetResolver::SOURCE_PROPOSED ? $text.' (مقترح)' : $text;
    }

    private function exclusionNotes(array $included, array $moduleKeys): array
    {
        $excluded = array_diff($moduleKeys, $included);
        if ($excluded === [] || $included === []) {
            return [];
        }

        $labels = array_map(fn (string $key): string => $this->registry->module($key)['label_ar'], $excluded);

        return ['لا يشمل: '.implode('، ', $labels)];
    }

    private function breakdown(array $calculator, array $moduleKeys, array $definition, array $totals, array $requiredStages): array
    {
        $rows = [];
        foreach ($moduleKeys as $key) {
            $moduleDefinition = $this->registry->moduleDefinition($key);
            $supported = in_array($key, $definition['applicable_modules'], true);
            foreach ($requiredStages as $stage) {
                $supported = $supported && $moduleDefinition->supportsStage(self::DERIVED_STAGES[$stage] ?? $stage);
            }

            $value = $supported ? $this->compute($calculator, [$key], $totals, 'current')['value'] : null;
            $rows[$key] = [
                'label' => $this->registry->module($key)['label_ar'],
                'value' => $value,
                'formatted' => $supported ? $this->formatter->format($value, $definition['unit']) : 'لا ينطبق',
                'reason' => $supported ? null : $moduleDefinition->unavailableReason($requiredStages[0]),
            ];
        }

        return $rows;
    }

    private function moduleSummaries(array $moduleKeys, array $totals, KpiScope $scope): array
    {
        $summaries = [];
        foreach ($moduleKeys as $key) {
            $module = $this->registry->module($key);
            $definition = $this->registry->moduleDefinition($key);
            $current = $totals[$key]['current'];
            $stages = [];
            foreach (KpiModuleDefinition::STAGES as $stage) {
                $supported = $definition->supportsStage($stage);
                $value = $supported && $current !== null ? $current[$stage] : null;
                $stages[$stage] = [
                    'label' => $this->registry->stages()[$stage]['label_ar'],
                    'value' => $value,
                    'formatted' => ! $supported ? 'لا ينطبق' : $this->formatter->format($value, 'count'),
                    'reason' => $supported ? ($current === null ? self::NO_BRANCHES_REASON : null) : $definition->unavailableReason($stage),
                ];
            }

            $summaries[$key] = [
                'key' => $key,
                'label' => $module['label_ar'],
                'icon' => $module['icon'],
                'accent' => $module['accent'],
                'records' => $current['records'] ?? null,
                'stages' => $stages,
                'notes' => $definition->notes(),
            ];
        }

        return $summaries;
    }

    private function eventsByModuleChart(array $moduleKeys, array $totals): array
    {
        $datasets = [];
        foreach (KpiModuleDefinition::STAGES as $stage) {
            $datasets[] = [
                'key' => $stage,
                'label' => $this->registry->stages()[$stage]['label_ar'],
                'data' => array_map(fn (string $key) => $totals[$key]['current'][$stage] ?? null, $moduleKeys),
            ];
        }

        return [
            'labels' => array_map(fn (string $key): string => $this->registry->module($key)['label_ar'], $moduleKeys),
            'datasets' => $datasets,
            'available' => $this->anyRecords($moduleKeys, $totals),
        ];
    }

    /** Exclusive buckets derived from the nested cumulative stages (each record counted once). */
    private function statusDistributionChart(array $moduleKeys, array $totals): array
    {
        $data = array_fill_keys(array_keys(self::BUCKETS), []);
        foreach ($moduleKeys as $key) {
            $current = $totals[$key]['current'];
            if ($current === null) {
                foreach ($data as $bucket => $values) {
                    $data[$bucket][] = null;
                }
                continue;
            }

            $executed = $current['executed'];
            $closed = $current['closed'];
            $data['draft'][] = $current['records'] - $current['planned'];
            $data['awaiting'][] = $current['planned'] - $current['approved'];
            $data['approved_open'][] = $current['approved'] - ($executed ?? 0);
            $data['executed_open'][] = $executed === null ? null : $executed - ($closed ?? 0);
            $data['closed'][] = $closed;
        }

        $datasets = [];
        foreach (self::BUCKETS as $bucket => $label) {
            $datasets[] = ['key' => $bucket, 'label' => $label, 'data' => $data[$bucket]];
        }

        return [
            'labels' => array_map(fn (string $key): string => $this->registry->module($key)['label_ar'], $moduleKeys),
            'datasets' => $datasets,
            'available' => $this->anyRecords($moduleKeys, $totals),
        ];
    }

    /** Planned records per month of the selected year, per module. */
    private function monthlyTrendChart(array $moduleKeys, KpiPeriod $period, KpiScope $scope): array
    {
        $year = new KpiPeriod($period->year);
        $labels = array_values(KpiPeriod::MONTHS_AR);
        $datasets = [];
        $available = false;

        if ($scope->hasNoBranches()) {
            return ['labels' => $labels, 'datasets' => [], 'available' => false, 'year' => $period->year];
        }

        foreach ($moduleKeys as $key) {
            $definition = $this->registry->moduleDefinition($key);
            $monthExpression = $this->monthExpression($definition->dateExpression());
            $query = $definition->liveQuery();
            $definition->applyBranchScope($query, $scope->branchIds());
            $rows = $query
                ->whereBetween($definition->dateExpression(), [$year->startDate(), $year->endDate()])
                ->selectRaw($monthExpression.' AS month_number, SUM(CASE WHEN '.$definition->cumulativeStageSql()['planned'].' THEN 1 ELSE 0 END) AS planned')
                ->groupBy('month_number')
                ->pluck('planned', 'month_number');

            $series = [];
            foreach (range(1, 12) as $month) {
                $series[] = (int) ($rows[$month] ?? 0);
            }
            $available = $available || array_sum($series) > 0;
            $datasets[] = ['key' => $key, 'label' => $this->registry->module($key)['label_ar'], 'data' => $series];
        }

        return ['labels' => $labels, 'datasets' => $datasets, 'available' => $available, 'year' => $period->year];
    }

    private function anyRecords(array $moduleKeys, array $totals): bool
    {
        foreach ($moduleKeys as $key) {
            if (($totals[$key]['current']['records'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function monthExpression(string $column): string
    {
        switch (DB::connection()->getDriverName()) {
            case 'sqlite':
                return "CAST(strftime('%m', ".$column.') AS INTEGER)';
            case 'pgsql':
                return 'EXTRACT(MONTH FROM '.$column.')';
            default:
                return 'MONTH('.$column.')';
        }
    }
}
