<?php

namespace App\Services\Kpi;

use App\Services\Kpi\Modules\KpiModuleDefinition;
use App\Services\Kpi\Support\KpiSql;

/**
 * Calculates catalogue KPIs from module metric sources.
 *
 * Engine: for a grouping (period pair, branch, or month of the year) each module metric source
 * runs ONE grouped aggregate query containing only the metrics the requested KPIs need. KPIs
 * are then evaluated in PHP from those aggregates (Σ numerator ÷ Σ denominator per contributing
 * module). No event rows are loaded into PHP.
 */
class KpiCalculatorService
{
    public const GROUP_PERIOD = 'period';
    public const GROUP_BRANCH = 'branch';
    public const GROUP_MONTH = 'month';

    public const NO_BRANCHES_REASON = 'لا توجد فروع ضمن نطاق صلاحياتك؛ لا يمكن عرض بيانات.';

    private KpiRegistry $registry;
    private KpiTargetResolver $targets;
    private KpiFormatter $formatter;

    public function __construct(KpiRegistry $registry, KpiTargetResolver $targets, KpiFormatter $formatter)
    {
        $this->registry = $registry;
        $this->targets = $targets;
        $this->formatter = $formatter;
    }

    /* ------------------------------------------------------------------ datasets */

    /**
     * @return array{grouping: string, scoped_out: bool, values: array<string, array<string, array<string, float>>>, defined: array<string, array<string, bool>>}
     */
    public function dataset(array $moduleKeys, string $grouping, KpiPeriod $period, KpiScope $scope, array $metricNames): array
    {
        $data = ['grouping' => $grouping, 'scoped_out' => $scope->hasNoBranches(), 'values' => [], 'defined' => []];
        if ($scope->hasNoBranches()) {
            return $data;
        }

        $wanted = array_flip($metricNames);
        foreach ($moduleKeys as $key) {
            $data['values'][$key] = [];
            $data['defined'][$key] = [];

            foreach ($this->registry->moduleDefinition($key)->metricSources() as $source) {
                $metrics = array_filter(array_intersect_key($source->metrics, $wanted), static fn ($sql) => $sql !== null);
                if ($metrics === []) {
                    continue;
                }

                $built = $source->build($scope->branchIds(), $grouping === self::GROUP_BRANCH);
                if ($built === null) {
                    continue;
                }
                [$query, $branchKey] = $built;
                [$from, $to, $groupExpression] = $this->groupingBounds($grouping, $period, $source->dateExpression, $branchKey);

                $selects = [$groupExpression.' AS group_key'];
                foreach ($metrics as $name => $sql) {
                    $selects[] = $sql.' AS '.$name;
                    $data['defined'][$key][$name] = true;
                }

                $rows = $query->whereBetween($source->dateExpression, [$from, $to])
                    ->selectRaw(implode(', ', $selects))
                    ->groupBy('group_key')
                    ->get();

                foreach ($rows as $row) {
                    if ($row->group_key === null || $row->group_key === '') {
                        continue;
                    }
                    $group = (string) $row->group_key;
                    foreach (array_keys($metrics) as $name) {
                        $data['values'][$key][$group][$name] = ($data['values'][$key][$group][$name] ?? 0) + (float) $row->{$name};
                    }
                }
            }
        }

        return $data;
    }

    /** Metric value, 0 when the group has no rows, null when the module cannot provide the metric. */
    public function metric(array $data, string $module, string $group, string $name): ?float
    {
        if (! ($data['defined'][$module][$name] ?? false)) {
            return null;
        }

        return (float) ($data['values'][$module][$group][$name] ?? 0);
    }

    private function groupingBounds(string $grouping, KpiPeriod $period, string $date, ?string $branchKey): array
    {
        switch ($grouping) {
            case self::GROUP_BRANCH:
                return [$period->startDate(), $period->endBound(), $branchKey];
            case self::GROUP_MONTH:
                $year = new KpiPeriod($period->year);

                return [$year->startDate(), $year->endBound(), KpiSql::month($date)];
            default:
                return [
                    $period->previous()->startDate(),
                    $period->endBound(),
                    'CASE WHEN '.$date.' >= '.KpiSql::literal($period->startDate())." THEN 'current' ELSE 'previous' END",
                ];
        }
    }

    /* ------------------------------------------------------------------ KPI evaluation */

    /** @return array<string, KpiResult> */
    public function results(array $definitions, array $moduleKeys, KpiPeriod $period, KpiScope $scope, ?array $data = null): array
    {
        $data = $data ?? $this->dataset($moduleKeys, self::GROUP_PERIOD, $period, $scope, $this->registry->metricsFor($definitions));
        $results = [];
        foreach ($definitions as $code => $definition) {
            $results[$code] = $this->evaluate($definition, $moduleKeys, $data, $period);
        }

        return $results;
    }

    /**
     * Value of one KPI for one dataset group (e.g. a branch id or a month number), without trend.
     *
     * @return array{value: float|int|null, formatted: string}
     */
    public function valueFor(array $definition, array $moduleKeys, array $data, string $group, KpiPeriod $period): array
    {
        [$contributors] = $this->contributors($definition, $moduleKeys, $data);
        $value = $contributors === [] ? null : $this->compute($definition, $contributors, $data, $group, $period)['value'];

        return ['value' => $value, 'formatted' => $this->formatter->format($value, $definition['unit'])];
    }

    private function evaluate(array $definition, array $moduleKeys, array $data, KpiPeriod $period): KpiResult
    {
        [$contributors, $excluded] = $this->contributors($definition, $moduleKeys, $data);
        $unit = $definition['unit'];

        if ($contributors === []) {
            $onlyNotApplicable = $excluded !== [] && collect($excluded)->every(fn (array $row): bool => $row['status'] === KpiResult::DATA_NOT_APPLICABLE);
            $status = $definition['unavailable_reason'] === null && $onlyNotApplicable ? KpiResult::DATA_NOT_APPLICABLE : KpiResult::DATA_UNAVAILABLE;
            $current = $previous = ['value' => null, 'numerator' => null, 'denominator' => null, 'records' => 0];
        } else {
            $status = collect($contributors)->contains(fn (array $spec): bool => $spec['status'] === KpiResult::DATA_PARTIAL)
                ? KpiResult::DATA_PARTIAL
                : KpiResult::DATA_READY;
            $current = $this->compute($definition, $contributors, $data, 'current', $period);
            $previous = $this->compute($definition, $contributors, $data, 'previous', $period->previous());
        }

        $missingReason = null;
        if ($definition['unavailable_reason']) {
            $missingReason = $definition['unavailable_reason'];
        } elseif ($data['scoped_out']) {
            $missingReason = self::NO_BRANCHES_REASON;
        } elseif ($contributors === []) {
            $missingReason = collect($excluded)->pluck('reason')->filter()->first() ?? 'لا تتوفر بيانات لهذا المؤشر ضمن الوحدات المختارة.';
        } elseif ($current['value'] === null) {
            $missingReason = 'لا توجد سجلات كافية لحساب المؤشر ضمن الفترة.';
        }

        $target = $this->targets->resolve($definition);
        $achieved = $this->targets->achieved($current['value'] === null ? null : (float) $current['value'], $target);

        return new KpiResult([
            'code' => $definition['code'],
            'name' => $definition['name_ar'],
            'description' => $definition['description_ar'],
            'category' => $definition['category'] ?? 'planning',
            'icon' => $definition['icon'],
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
            'dataStatus' => $status,
            'modules' => array_map(fn (string $key): string => $this->registry->moduleLabel($key), array_keys($contributors)),
            'notes' => $this->notes($definition, $contributors, $excluded),
            'period' => $period->label(),
            'comparisonPeriod' => $period->previous()->label(),
            'trend' => $this->trend($current, $previous, $unit, $definition['calculator']['type'] ?? 'sum'),
            'dataAvailable' => $current['value'] !== null,
            'missingReason' => $missingReason,
            'breakdown' => $this->breakdown($definition, $moduleKeys, $contributors, $excluded, $data, $period),
            'drilldown' => $definition['drilldown'] ? ['route' => 'kpi.reports.show', 'parameters' => $definition['drilldown']] : null,
        ]);
    }

    /**
     * Split selected modules into contributors (with their metric names) and excluded modules
     * (with status and reason). A module contributes only when it declares availability AND its
     * metric sources define the needed metrics in the current scope.
     */
    private function contributors(array $definition, array $moduleKeys, array $data): array
    {
        $calculator = $definition['calculator'] ?? null;
        $contributors = [];
        $excluded = [];

        foreach ($moduleKeys as $key) {
            if (! $calculator || $definition['unavailable_reason']) {
                $excluded[$key] = ['status' => KpiResult::DATA_UNAVAILABLE, 'reason' => $definition['unavailable_reason']];
                continue;
            }
            $blockers = array_intersect($definition['exclusive_with'][$key] ?? [], $moduleKeys);
            if ($blockers !== []) {
                $excluded[$key] = ['status' => KpiResult::DATA_NOT_APPLICABLE, 'reason' => 'لا يُضاف للمجموع مع '
                    .implode('، ', array_map(fn (string $blocker): string => $this->registry->moduleLabel($blocker), $blockers))
                    .' لأن تنفيذ الفروع للأجندة يُسجَّل كأنشطة شهرية (تجنباً للعد المزدوج)؛ تظهر أرقامه في بطاقته الخاصة.'];
                continue;
            }
            if (isset($definition['availability'][$key])) {
                $spec = $definition['availability'][$key];
                $spec = is_array($spec) ? $spec : ['status' => $spec];
                $numerator = $spec['numerator'] ?? $calculator['numerator'];
                $denominator = $spec['denominator'] ?? ($calculator['denominator'] ?? null);
                $defined = ($data['defined'][$key][$numerator] ?? false) && ($denominator === null || ($data['defined'][$key][$denominator] ?? false));
                if ($defined) {
                    $contributors[$key] = ['status' => $spec['status'], 'numerator' => $numerator, 'denominator' => $denominator];
                } else {
                    $excluded[$key] = ['status' => KpiResult::DATA_UNAVAILABLE, 'reason' => $data['scoped_out']
                        ? self::NO_BRANCHES_REASON
                        : 'البيانات المطلوبة غير متاحة ضمن نطاقك أو على قاعدة البيانات الحالية.'];
                }
            } elseif (isset($definition['not_applicable'][$key])) {
                $excluded[$key] = ['status' => KpiResult::DATA_NOT_APPLICABLE, 'reason' => $definition['not_applicable'][$key]];
            } elseif (isset($definition['unavailable'][$key])) {
                $excluded[$key] = ['status' => KpiResult::DATA_UNAVAILABLE, 'reason' => $definition['unavailable'][$key]];
            } else {
                $excluded[$key] = ['status' => KpiResult::DATA_NOT_APPLICABLE, 'reason' => null];
            }
        }

        return [$contributors, $excluded];
    }

    /** @return array{value: float|int|null, numerator: float|null, denominator: float|null, records: int} */
    private function compute(array $definition, array $contributors, array $data, string $group, KpiPeriod $period): array
    {
        $numerator = 0.0;
        $denominator = 0.0;
        $records = 0;
        foreach ($contributors as $key => $spec) {
            $numerator += (float) $this->metric($data, $key, $group, $spec['numerator']);
            if ($spec['denominator'] !== null) {
                $denominator += (float) $this->metric($data, $key, $group, $spec['denominator']);
            }
            $records += (int) ($this->metric($data, $key, $group, 'records') ?? 0);
        }

        $calculator = $definition['calculator'];
        switch ($calculator['type']) {
            case 'ratio':
                $value = $denominator > 0 ? $numerator / $denominator * (float) ($calculator['scale'] ?? 100) : null;
                break;
            case 'per_month':
                $months = $period->monthsElapsed();
                $value = $months > 0 ? $numerator / $months : null;
                break;
            default:
                $value = $numerator;
        }

        if ($value !== null) {
            $precision = $this->formatter->precision($definition['unit']);
            $value = $precision === 0 ? (int) round($value) : round($value, $precision);
        }

        return [
            'value' => $value,
            'numerator' => $numerator,
            'denominator' => $calculator['type'] === 'ratio' ? $denominator : null,
            'records' => $records,
        ];
    }

    private function notes(array $definition, array $contributors, array $excluded): array
    {
        $notes = [];
        if ($contributors !== []) {
            foreach ($excluded as $key => $row) {
                if ($row['reason'] !== null || $row['status'] === KpiResult::DATA_UNAVAILABLE) {
                    $notes[] = 'لا يشمل '.$this->registry->moduleLabel($key).($row['reason'] ? ': '.$row['reason'] : '.');
                }
            }
        }

        return array_merge($notes, $definition['limitations'] ?? []);
    }

    private function breakdown(array $definition, array $moduleKeys, array $contributors, array $excluded, array $data, KpiPeriod $period): array
    {
        $rows = [];
        foreach ($moduleKeys as $key) {
            if (isset($contributors[$key])) {
                $value = $this->compute($definition, [$key => $contributors[$key]], $data, 'current', $period)['value'];
                $rows[$key] = [
                    'label' => $this->registry->moduleLabel($key),
                    'value' => $value,
                    'formatted' => $this->formatter->format($value, $definition['unit']),
                    'status' => $contributors[$key]['status'],
                    'reason' => null,
                ];
                continue;
            }

            $row = $excluded[$key];
            $rows[$key] = [
                'label' => $this->registry->moduleLabel($key),
                'value' => null,
                'formatted' => $row['status'] === KpiResult::DATA_NOT_APPLICABLE ? 'لا ينطبق' : 'غير متاح',
                'status' => $row['status'],
                'reason' => $row['reason'],
            ];
        }

        return $rows;
    }

    /** Trend only when both periods hold comparable data (records, or a non-zero denominator for ratios). */
    private function trend(array $current, array $previous, string $unit, string $type): ?array
    {
        if ($current['value'] === null || $previous['value'] === null) {
            return null;
        }
        if ($type !== 'ratio' && ($current['records'] === 0 || $previous['records'] === 0)) {
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

    /* ------------------------------------------------------------------ module summaries */

    /** Stage cards per module from a period dataset that includes the stage metrics. */
    public function moduleSummaries(array $moduleKeys, array $data): array
    {
        $summaries = [];
        foreach ($moduleKeys as $key) {
            $module = $this->registry->module($key);
            $definition = $this->registry->moduleDefinition($key);
            $stages = [];
            foreach (KpiModuleDefinition::STAGES as $stage) {
                $supported = $definition->supportsStage($stage);
                $value = $supported ? $this->metric($data, $key, 'current', $stage) : null;
                $stages[$stage] = [
                    'label' => $this->registry->stages()[$stage]['label_ar'],
                    'value' => $value,
                    'formatted' => ! $supported ? 'لا ينطبق' : $this->formatter->format($value, 'count'),
                    'reason' => $supported ? ($data['scoped_out'] ? self::NO_BRANCHES_REASON : null) : $definition->unavailableReason($stage),
                ];
            }

            $records = $this->metric($data, $key, 'current', 'records');
            $summaries[$key] = [
                'key' => $key,
                'label' => $module['label_ar'],
                'icon' => $module['icon'],
                'accent' => $module['accent'],
                'records' => $records === null ? null : (int) $records,
                'stages' => $stages,
                'notes' => $definition->notes(),
            ];
        }

        return $summaries;
    }

    /** Metrics needed by module summary cards. */
    public function stageMetricNames(): array
    {
        return ['records', 'planned', 'approved', 'executed', 'closed', 'approved_due', 'executed_due'];
    }
}
