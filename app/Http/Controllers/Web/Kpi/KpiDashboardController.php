<?php

namespace App\Http\Controllers\Web\Kpi;

use App\Http\Controllers\Concerns\StreamsCsv;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Kpi\Concerns\ParsesKpiFilters;
use App\Models\Branch;
use App\Services\Kpi\KpiCalculatorService;
use App\Services\Kpi\KpiChartBuilder;
use App\Services\Kpi\KpiPeriod;
use App\Services\Kpi\KpiRegistry;
use App\Services\Kpi\KpiReportService;
use App\Services\Kpi\KpiResult;
use App\Services\Kpi\KpiScope;
use Illuminate\Http\Request;

/**
 * KPI section (مؤشرات الأداء والتقارير). Access: role_or_permission:super_admin|kpi.view (route).
 * Branch scope is resolved by KpiScope for every page; calculations live in app/Services/Kpi.
 */
class KpiDashboardController extends Controller
{
    use ParsesKpiFilters;
    use StreamsCsv;

    /** Branch comparison columns: column key => KPI code. */
    private const COMPARISON_COLUMNS = [
        'planned' => 'BR-01',
        'approved' => 'BR-02',
        'executed' => 'BR-03',
        'execution_rate' => 'BR-04',
        'attendance' => 'BR-08',
        'closure_rate' => 'BR-07',
    ];

    private const BRANCH_MONTH_METRICS = ['records', 'planned', 'approved', 'executed', 'approved_due', 'executed_due', 'attendance_planned', 'attendance_actual'];

    private KpiRegistry $registry;
    private KpiCalculatorService $calculator;
    private KpiChartBuilder $charts;

    public function __construct(KpiRegistry $registry, KpiCalculatorService $calculator, KpiChartBuilder $charts)
    {
        $this->registry = $registry;
        $this->calculator = $calculator;
        $this->charts = $charts;
    }

    public function overview(Request $request)
    {
        $filters = $this->filters($request, array_keys($this->registry->modules()));
        $moduleKeys = $filters['module'] ? [$filters['module']] : array_keys($this->registry->modules());
        $definitions = $this->registry->definitions('overview');

        $periodData = $this->calculator->dataset($moduleKeys, KpiCalculatorService::GROUP_PERIOD, $filters['period'], $filters['scope'],
            array_merge($this->registry->metricsFor($definitions), $this->calculator->stageMetricNames()));
        $monthData = $this->calculator->dataset($moduleKeys, KpiCalculatorService::GROUP_MONTH, $filters['period'], $filters['scope'], ['records', 'planned']);
        $charts = $this->charts->overview($moduleKeys, $periodData, $monthData);
        if ($filters['module']) {
            $charts = array_values(array_filter($charts, fn (array $chart): bool => $chart['id'] !== 'eventsByModule'));
        }

        return view('pages.kpi.overview', [
            'filters' => $filters,
            'results' => $this->calculator->results($definitions, $moduleKeys, $filters['period'], $filters['scope'], $periodData),
            'modules' => $this->calculator->moduleSummaries($moduleKeys, $periodData),
            'charts' => $charts,
            'stages' => $this->registry->stages(),
        ] + $this->filterOptions($filters));
    }

    public function events(Request $request)
    {
        $filters = $this->filters($request, []);
        $moduleKeys = array_keys($this->registry->modules());
        $data = $this->calculator->dataset($moduleKeys, KpiCalculatorService::GROUP_PERIOD, $filters['period'], $filters['scope'], $this->calculator->stageMetricNames());

        return view('pages.kpi.events', [
            'filters' => $filters,
            'modules' => $this->calculator->moduleSummaries($moduleKeys, $data),
        ] + $this->filterOptions($filters));
    }

    public function event(Request $request, string $module)
    {
        abort_unless($this->registry->hasModule($module), 404);
        $filters = $this->filters($request, []);
        $definitions = $this->registry->definitions('module', $module);

        $periodData = $this->calculator->dataset([$module], KpiCalculatorService::GROUP_PERIOD, $filters['period'], $filters['scope'],
            array_merge($this->registry->metricsFor($definitions), $this->calculator->stageMetricNames(), $this->charts->periodMetrics($module)));
        $monthData = $this->calculator->dataset([$module], KpiCalculatorService::GROUP_MONTH, $filters['period'], $filters['scope'], $this->charts->monthMetrics($module));
        $results = $this->calculator->results($definitions, [$module], $filters['period'], $filters['scope'], $periodData);

        return view('pages.kpi.event', [
            'filters' => $filters,
            'module' => $this->registry->module($module) + ['key' => $module],
            'sections' => $this->sections($results),
            'unavailable' => $this->unavailable($results),
            'modules' => $this->calculator->moduleSummaries([$module], $periodData),
            'charts' => $this->charts->module($module, $periodData, $monthData, $filters['period'], $filters['scope']),
        ] + $this->filterOptions($filters));
    }

    public function branches(Request $request)
    {
        $branchModules = $this->registry->branchModules();
        $filters = $this->filters($request, $branchModules);
        $moduleKeys = $filters['module'] ? [$filters['module']] : $branchModules;
        $definitions = array_map(fn (string $code): array => $this->registry->definition($code), self::COMPARISON_COLUMNS);
        $data = $this->calculator->dataset($moduleKeys, KpiCalculatorService::GROUP_BRANCH, $filters['period'], $filters['scope'], $this->registry->metricsFor($definitions));

        $branches = $filters['scope']->availableBranches();
        if ($filters['branch_id'] !== null) {
            $branches = $branches->where('id', $filters['branch_id']);
        }

        $rows = $data['scoped_out'] ? collect() : $branches->map(function (Branch $branch) use ($definitions, $moduleKeys, $data, $filters): array {
            $values = [];
            foreach ($definitions as $column => $definition) {
                $values[$column] = $this->calculator->valueFor($definition, $moduleKeys, $data, (string) $branch->id, $filters['period']);
            }

            return ['branch_id' => (int) $branch->id, 'branch' => $branch->name, 'values' => $values];
        });

        $sort = in_array($request->query('sort'), array_merge(['branch'], array_keys(self::COMPARISON_COLUMNS)), true) ? $request->query('sort') : 'branch';
        $direction = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        $rows = $rows->sort(function (array $left, array $right) use ($sort, $direction): int {
            if ($sort === 'branch') {
                $result = strcmp($left['branch'], $right['branch']);
            } else {
                $a = $left['values'][$sort]['value'];
                $b = $right['values'][$sort]['value'];
                if ($a === null || $b === null) {
                    return $a === $b ? 0 : ($a === null ? 1 : -1); // unavailable values always last
                }
                $result = $a <=> $b;
            }

            return $direction === 'desc' ? -$result : $result;
        })->values();

        return view('pages.kpi.branches', [
            'filters' => $filters,
            'rows' => $rows,
            'columns' => collect($definitions)->map(fn (array $definition): array => ['code' => $definition['code'], 'label' => $definition['name_ar'], 'description' => $definition['description_ar']])->all(),
            'sort' => $sort,
            'direction' => $direction,
        ] + $this->filterOptions($filters, $branchModules));
    }

    public function branch(Request $request, int $branch)
    {
        $filters = $this->filters($request, [], $branch);
        $branchModel = Branch::query()->findOrFail($branch);
        $branchModules = $this->registry->branchModules();
        $allModules = array_keys($this->registry->modules());
        $definitions = $this->registry->definitions('branch');

        $periodData = $this->calculator->dataset($allModules, KpiCalculatorService::GROUP_PERIOD, $filters['period'], $filters['scope'],
            array_merge($this->registry->metricsFor($definitions), $this->calculator->stageMetricNames()));
        $monthData = $this->calculator->dataset($branchModules, KpiCalculatorService::GROUP_MONTH, $filters['period'], $filters['scope'], self::BRANCH_MONTH_METRICS);
        $results = $this->calculator->results($definitions, $allModules, $filters['period'], $filters['scope'], $periodData);

        $volume = [];
        foreach (KpiPeriod::MONTHS_AR as $number => $label) {
            $row = ['month' => $label];
            foreach (['planned', 'approved', 'executed'] as $stage) {
                $total = null;
                foreach ($branchModules as $key) {
                    $value = $this->calculator->metric($monthData, $key, (string) $number, $stage);
                    $total = $value === null ? $total : (int) $total + (int) $value;
                }
                $row[$stage] = $total;
            }
            $volume[] = $row;
        }

        return view('pages.kpi.branch', [
            'filters' => $filters,
            'branch' => $branchModel,
            'results' => array_filter($results, fn (KpiResult $result): bool => ! $this->isUnavailable($result)),
            'unavailable' => $this->unavailable($results),
            'modules' => $this->calculator->moduleSummaries($branchModules, $periodData),
            'volume' => $volume,
            'charts' => $this->charts->branch($branchModules, $periodData, $monthData, $filters['period']),
        ] + $this->filterOptions($filters));
    }

    public function report(Request $request, string $report, KpiReportService $reports)
    {
        abort_unless(KpiReportService::exists($report), 404);
        $filters = $this->filters($request, []);
        $module = KpiReportService::moduleFor($report, $request->query('module'));

        return view('pages.kpi.report', [
            'filters' => $filters,
            'reportKey' => $report,
            'report' => $reports->build($report, $module, $filters['period'], $filters['scope']),
            'canExport' => KpiScope::canExport($request->user()),
        ] + $this->filterOptions($filters));
    }

    /** Real CSV of the same query as the HTML report (same filters, scope and ordering), streamed. */
    public function reportExport(Request $request, string $report, KpiReportService $reports)
    {
        abort_unless(KpiReportService::exists($report), 404);
        abort_unless(KpiScope::canExport($request->user()), 403);
        $filters = $this->filters($request, []);
        $module = KpiReportService::moduleFor($report, $request->query('module'));
        $query = $reports->query($report, $module, $filters['period'], $filters['scope']);
        [$headers, $mapper] = $reports->csvFor($report, $module);

        return $this->csvResponse(
            'kpi-'.$report.'-'.$module.'-'.$filters['year'].($filters['month'] ? '-'.$filters['month'] : '').'.csv',
            $headers,
            $query ? $query->cursor()->map($mapper) : [],
            true
        );
    }

    /* ------------------------------------------------------------------ helpers */

    /** Group available results by catalogue category, in catalogue order. */
    private function sections(array $results): array
    {
        $sections = [];
        foreach ($this->registry->categories() as $key => $label) {
            $items = array_filter($results, fn (KpiResult $result): bool => $result->category === $key && ! $this->isUnavailable($result));
            if ($items !== []) {
                $sections[$key] = ['label' => $label, 'results' => $items];
            }
        }

        return $sections;
    }

    private function unavailable(array $results): array
    {
        return array_filter($results, fn (KpiResult $result): bool => $this->isUnavailable($result));
    }

    private function isUnavailable(KpiResult $result): bool
    {
        return in_array($result->dataStatus, [KpiResult::DATA_UNAVAILABLE, KpiResult::DATA_NOT_APPLICABLE], true);
    }
}
