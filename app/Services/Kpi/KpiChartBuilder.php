<?php

namespace App\Services\Kpi;

use App\Models\Branch;
use App\Services\Kpi\Modules\KpiModuleDefinition;
use App\Services\Kpi\Support\KpiSql;

/**
 * Builds server-prepared chart specs from KPI datasets. The browser only renders them.
 *
 * Spec: {id, title, description, icon, type (bar|line|hbar|hbar-stacked), unit, labels,
 * datasets: [{label, data, color | colors}], available}
 */
class KpiChartBuilder
{
    private const BUCKETS = [
        'draft' => 'مسودة',
        'awaiting' => 'قيد الاعتماد / معاد',
        'approved_open' => 'معتمد بانتظار التنفيذ',
        'executed_open' => 'منفذ بانتظار الإغلاق',
        'closed' => 'مغلق',
    ];

    private KpiRegistry $registry;
    private KpiCalculatorService $calculator;

    public function __construct(KpiRegistry $registry, KpiCalculatorService $calculator)
    {
        $this->registry = $registry;
        $this->calculator = $calculator;
    }

    /** Metrics each module needs in its month dataset for its charts. */
    public function monthMetrics(string $module): array
    {
        return [
            'agenda' => ['records', 'planned', 'approved'],
            'monthly' => ['records', 'planned', 'executed', 'attendance_planned', 'attendance_actual'],
            'ramadan' => ['records', 'planned', 'executed', 'meals_planned', 'meals_actual', 'attendance_planned', 'attendance_actual'],
            'bazaar' => ['records', 'planned', 'executed', 'tables_planned', 'tables_booked'],
        ][$module] ?? ['records', 'planned'];
    }

    /** Metrics each module needs in its period dataset for its charts. */
    public function periodMetrics(string $module): array
    {
        return [
            'monthly' => ['evaluated'],
            'ramadan' => ['matched', 'mismatched', 'not_observed'],
            'bazaar' => ['tables_paid', 'tables_partial', 'tables_unpaid', 'discount_pending', 'discount_approved', 'discount_returned', 'discount_rejected'],
        ][$module] ?? [];
    }

    /* ------------------------------------------------------------------ overview */

    public function overview(array $moduleKeys, array $periodData, array $monthData): array
    {
        $labels = array_map(fn (string $key): string => $this->registry->moduleLabel($key), $moduleKeys);
        $stageDatasets = [];
        foreach (KpiModuleDefinition::STAGES as $stage) {
            $stageDatasets[] = $this->dataset($this->registry->stages()[$stage]['label_ar'], array_map(
                fn (string $key) => $this->registry->moduleDefinition($key)->supportsStage($stage) ? $this->calculator->metric($periodData, $key, 'current', $stage) : null,
                $moduleKeys
            ), 'stage-'.$stage);
        }

        return [
            $this->chart('eventsByModule', 'الفعاليات حسب النوع والمرحلة', 'عدد السجلات الحالية التي وصلت كل مرحلة ضمن الفترة.', 'fa-chart-column', 'bar', $labels, $stageDatasets, $this->hasRecords($moduleKeys, $periodData)),
            $this->statusDistribution($moduleKeys, $periodData),
            $this->chart('monthlyTrend', 'الاتجاه الشهري للفعاليات المخططة', 'الفعاليات المخططة في كل شهر من السنة المختارة.', 'fa-chart-line', 'line', $this->monthLabels(),
                array_map(fn (string $key): array => $this->dataset($this->registry->moduleLabel($key), $this->monthSeries($monthData, $key, 'planned'), $key), $moduleKeys),
                $this->hasMonthValues($monthData, $moduleKeys, 'planned')),
        ];
    }

    /* ------------------------------------------------------------------ modules */

    public function module(string $module, array $periodData, array $monthData, KpiPeriod $period, KpiScope $scope): array
    {
        $months = $this->monthLabels();
        $series = fn (string $metric): array => $this->monthSeries($monthData, $module, $metric);
        $metric = fn (string $name): ?float => $this->calculator->metric($periodData, $module, 'current', $name);
        $recordsAvailable = $this->hasRecords([$module], $periodData);

        switch ($module) {
            case 'agenda':
                return [
                    $this->chart('agendaPlannedApproved', 'المخطط مقابل المعتمد شهرياً', 'فعاليات الأجندة المقدّمة والمنشورة لكل شهر.', 'fa-chart-column', 'bar', $months,
                        [$this->dataset('مخطط', $series('planned'), 'stage-planned'), $this->dataset('معتمد', $series('approved'), 'stage-approved')], $this->hasMonthValues($monthData, [$module], 'planned')),
                    $this->agendaCategories($period, $scope),
                    $this->agendaParticipation($period, $scope),
                ];
            case 'monthly':
                $stages = ['planned' => 'مخطط', 'approved' => 'معتمد', 'executed' => 'منفذ', 'closed' => 'مغلق', 'evaluated' => 'مقيّم'];

                return [
                    $this->chart('monthlyPlannedExecuted', 'المخطط مقابل المنفذ شهرياً', 'الأنشطة المقدّمة والمنفذة لكل شهر.', 'fa-chart-column', 'bar', $months,
                        [$this->dataset('مخطط', $series('planned'), 'stage-planned'), $this->dataset('منفذ', $series('executed'), 'stage-executed')], $this->hasMonthValues($monthData, [$module], 'planned')),
                    $this->chart('monthlyLifecycle', 'مراحل دورة الحياة', 'عدد الأنشطة التي وصلت كل مرحلة ضمن الفترة.', 'fa-bars-staggered', 'bar', array_values($stages),
                        [$this->dataset('عدد الأنشطة', array_map($metric, array_keys($stages)), null, ['stage-planned', 'stage-approved', 'stage-executed', 'stage-closed', 'stage-evaluated'])], $recordsAvailable),
                    $this->chart('monthlyAttendance', 'الحضور المتوقع والفعلي', 'للأنشطة المنفذة التي تملك القيمتين.', 'fa-people-group', 'bar', $months,
                        [$this->dataset('متوقع', $series('attendance_planned'), 'stage-planned'), $this->dataset('فعلي', $series('attendance_actual'), 'stage-executed')], $this->hasMonthValues($monthData, [$module], 'attendance_planned')),
                ];
            case 'ramadan':
                $match = ['matched' => 'مطابق', 'mismatched' => 'غير مطابق', 'not_observed' => 'لم يُرصد'];

                return [
                    $this->chart('ramadanMeals', 'الوجبات المخططة والفعلية', 'للإفطارات المنفذة التي تملك القيمتين.', 'fa-utensils', 'bar', $months,
                        [$this->dataset('مخطط', $series('meals_planned'), 'stage-planned'), $this->dataset('فعلي', $series('meals_actual'), 'stage-executed')], $this->hasMonthValues($monthData, [$module], 'meals_planned')),
                    $this->chart('ramadanAttendance', 'الحضور المتوقع والفعلي', 'للإفطارات المنفذة التي تملك القيمتين.', 'fa-people-group', 'bar', $months,
                        [$this->dataset('متوقع', $series('attendance_planned'), 'stage-planned'), $this->dataset('فعلي', $series('attendance_actual'), 'stage-executed')], $this->hasMonthValues($monthData, [$module], 'attendance_planned')),
                    $this->chart('ramadanMatch', 'نتائج المطابقة في المتابعة', 'بنود تقارير المتابعة (آخر نسخة).', 'fa-equals', 'hbar', array_values($match),
                        [$this->dataset('عدد البنود', array_map($metric, array_keys($match)), null, ['bucket-closed', 'bucket-awaiting', 'bucket-draft'])], (float) array_sum(array_map(fn ($key) => (float) $metric($key), array_keys($match))) > 0),
                ];
            case 'bazaar':
                $payment = ['tables_paid' => 'مسدد', 'tables_partial' => 'مسدد جزئياً', 'tables_unpaid' => 'غير مسدد'];
                $discount = ['discount_pending' => 'بانتظار القرار', 'discount_approved' => 'معتمد', 'discount_returned' => 'معاد', 'discount_rejected' => 'مرفوض'];

                return [
                    $this->chart('bazaarOccupancy', 'الطاولات المخططة والمحجوزة', 'للبازارات المنفذة في كل شهر.', 'fa-table-cells', 'bar', $months,
                        [$this->dataset('مخطط', $series('tables_planned'), 'stage-planned'), $this->dataset('محجوز', $series('tables_booked'), 'stage-executed')], $this->hasMonthValues($monthData, [$module], 'tables_planned')),
                    $this->chart('bazaarPayments', 'حالة الدفع للطاولات المحجوزة', 'مشتقة من المبالغ المستحقة والمدفوعة.', 'fa-money-bill-wave', 'hbar', array_values($payment),
                        [$this->dataset('عدد الطاولات', array_map($metric, array_keys($payment)), null, ['bucket-closed', 'bucket-awaiting', 'bucket-draft'])], (float) array_sum(array_map(fn ($key) => (float) $metric($key), array_keys($payment))) > 0),
                    $this->chart('bazaarDiscounts', 'حالة طلبات الخصم', 'كل طلبات الخصم على طاولات البازارات المقدّمة.', 'fa-percent', 'hbar', array_values($discount),
                        [$this->dataset('عدد الطلبات', array_map($metric, array_keys($discount)), null, ['bucket-awaiting', 'bucket-closed', 'bucket-approved-open', 'bucket-draft'])], (float) array_sum(array_map(fn ($key) => (float) $metric($key), array_keys($discount))) > 0),
                ];
        }

        return [];
    }

    /* ------------------------------------------------------------------ branch */

    public function branch(array $moduleKeys, array $periodData, array $monthData, KpiPeriod $period): array
    {
        $months = $this->monthLabels();
        $sum = function (string $metric) use ($moduleKeys, $monthData): array {
            $values = [];
            foreach (range(1, 12) as $month) {
                $total = null;
                foreach ($moduleKeys as $key) {
                    $value = $this->calculator->metric($monthData, $key, (string) $month, $metric);
                    $total = $value === null ? $total : (float) $total + $value;
                }
                $values[] = $total;
            }

            return $values;
        };
        $rateSeries = function (string $code) use ($moduleKeys, $monthData, $period): array {
            $definition = $this->registry->definition($code);
            $values = [];
            foreach (range(1, 12) as $month) {
                $values[] = $this->calculator->valueFor($definition, $moduleKeys, $monthData, (string) $month, $period)['value'];
            }

            return $values;
        };
        $executionRate = $rateSeries('BR-04');
        $attendance = $rateSeries('BR-08');

        return [
            $this->chart('branchPlannedExecuted', 'المخطط والمعتمد والمنفذ شهرياً', 'فعاليات الفرع لكل شهر من السنة المختارة.', 'fa-chart-column', 'bar', $months,
                [$this->dataset('مخطط', $sum('planned'), 'stage-planned'), $this->dataset('معتمد', $sum('approved'), 'stage-approved'), $this->dataset('منفذ', $sum('executed'), 'stage-executed')],
                array_sum(array_map('floatval', $sum('planned'))) > 0),
            $this->chart('branchModules', 'توزيع الفعاليات حسب النوع', 'الفعاليات المخططة ضمن الفترة لكل نوع.', 'fa-chart-pie', 'hbar',
                array_map(fn (string $key): string => $this->registry->moduleLabel($key), $moduleKeys),
                [$this->dataset('مخطط', array_map(fn (string $key) => $this->calculator->metric($periodData, $key, 'current', 'planned'), $moduleKeys), null, $moduleKeys)],
                $this->hasRecords($moduleKeys, $periodData)),
            $this->chart('branchExecutionRate', 'اتجاه نسبة التنفيذ', 'المنفذ ÷ المعتمد المستحق لكل شهر.', 'fa-chart-line', 'line', $months,
                [$this->dataset('نسبة التنفيذ', $executionRate, 'stage-executed')], count(array_filter($executionRate, static fn ($value) => $value !== null)) > 0, '%'),
            $this->chart('branchAttendance', 'اتجاه تحقيق الحضور', 'الحضور الفعلي ÷ المتوقع لكل شهر (الأنشطة الشهرية والإفطارات).', 'fa-people-group', 'line', $months,
                [$this->dataset('تحقيق الحضور', $attendance, 'stage-approved')], count(array_filter($attendance, static fn ($value) => $value !== null)) > 0, '%'),
        ];
    }

    /* ------------------------------------------------------------------ agenda extras */

    private function agendaCategories(KpiPeriod $period, KpiScope $scope): array
    {
        $rows = collect();
        if (! $scope->hasNoBranches()) {
            $definition = $this->registry->moduleDefinition('agenda');
            $query = $definition->liveQuery('s');
            $scopeSql = $definition->branchScopeSql('s', $scope->branchIds());
            if (is_string($scopeSql)) {
                $query->whereRaw($scopeSql);
            }
            $rows = $query
                ->leftJoin('event_categories as ec', 'ec.id', '=', 's.event_category_id')
                ->whereBetween('s.event_date', [$period->startDate(), $period->endBound()])
                ->whereRaw($definition->stageSql('planned', 's'))
                ->selectRaw("COALESCE(ec.name, 'غير مصنف') AS label, COUNT(*) AS total")
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(12)
                ->get();
        }

        return $this->chart('agendaCategories', 'توزيع فئات الأجندة', 'الفعاليات المقدّمة حسب الفئة (أعلى 12).', 'fa-tags', 'hbar',
            $rows->pluck('label')->all(), [$this->dataset('فعاليات', $rows->pluck('total')->map(fn ($value) => (int) $value)->all(), 'stage-planned')], $rows->isNotEmpty());
    }

    private function agendaParticipation(KpiPeriod $period, KpiScope $scope): array
    {
        $data = $this->calculator->dataset(['agenda'], KpiCalculatorService::GROUP_BRANCH, $period, $scope, ['participants', 'participants_executed']);
        $values = collect($data['values']['agenda'] ?? [])->sortByDesc('participants')->take(15);
        $names = Branch::query()->whereIn('id', $values->keys()->map(fn ($id) => (int) $id)->all() ?: [0])->pluck('name', 'id');

        return $this->chart('agendaParticipation', 'مشاركة الفروع في الأجندة', 'عدد مشاركات كل فرع ومنها ما نُفّذ عبر الأنشطة الشهرية (أعلى 15).', 'fa-code-branch', 'hbar',
            $values->keys()->map(fn ($id) => (string) ($names[(int) $id] ?? '—'))->values()->all(),
            [
                $this->dataset('مشاركات', $values->pluck('participants')->map(fn ($value) => (int) $value)->values()->all(), 'stage-planned'),
                $this->dataset('منفذة', $values->map(fn ($row) => (int) ($row['participants_executed'] ?? 0))->values()->all(), 'stage-executed'),
            ],
            $values->isNotEmpty());
    }

    /* ------------------------------------------------------------------ helpers */

    private function statusDistribution(array $moduleKeys, array $periodData): array
    {
        $buckets = array_fill_keys(array_keys(self::BUCKETS), []);
        foreach ($moduleKeys as $key) {
            $value = fn (string $metric): ?float => $this->calculator->metric($periodData, $key, 'current', $metric);
            if ($value('records') === null) {
                foreach (array_keys($buckets) as $bucket) {
                    $buckets[$bucket][] = null;
                }
                continue;
            }
            $executed = $value('executed');
            $closed = $value('closed');
            $buckets['draft'][] = $value('records') - $value('planned');
            $buckets['awaiting'][] = $value('planned') - $value('approved');
            $buckets['approved_open'][] = $value('approved') - (float) $executed;
            $buckets['executed_open'][] = $executed === null ? null : $executed - (float) $closed;
            $buckets['closed'][] = $closed;
        }

        $datasets = [];
        foreach (self::BUCKETS as $bucket => $label) {
            $datasets[] = $this->dataset($label, $buckets[$bucket], 'bucket-'.str_replace('_', '-', $bucket));
        }

        return $this->chart('statusDistribution', 'توزيع الحالات', 'كل سجل يُحتسب مرة واحدة في أعلى مرحلة وصل إليها.', 'fa-bars-progress', 'hbar-stacked',
            array_map(fn (string $key): string => $this->registry->moduleLabel($key), $moduleKeys), $datasets, $this->hasRecords($moduleKeys, $periodData));
    }

    private function monthSeries(array $monthData, string $module, string $metric): array
    {
        $values = [];
        foreach (range(1, 12) as $month) {
            $values[] = $this->calculator->metric($monthData, $module, (string) $month, $metric);
        }

        return $values;
    }

    private function hasMonthValues(array $monthData, array $moduleKeys, string $metric): bool
    {
        foreach ($moduleKeys as $key) {
            if (array_sum(array_map('floatval', $this->monthSeries($monthData, $key, $metric))) > 0) {
                return true;
            }
        }

        return false;
    }

    private function hasRecords(array $moduleKeys, array $periodData): bool
    {
        foreach ($moduleKeys as $key) {
            if (($this->calculator->metric($periodData, $key, 'current', 'records') ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function monthLabels(): array
    {
        return array_values(KpiPeriod::MONTHS_AR);
    }

    private function dataset(string $label, array $data, ?string $color, ?array $colors = null): array
    {
        return array_filter(['label' => $label, 'data' => array_values($data), 'color' => $color, 'colors' => $colors], static fn ($value) => $value !== null);
    }

    private function chart(string $id, string $title, string $description, string $icon, string $type, array $labels, array $datasets, bool $available, ?string $unit = null): array
    {
        return compact('id', 'title', 'description', 'icon', 'type', 'labels', 'datasets', 'available', 'unit');
    }
}
