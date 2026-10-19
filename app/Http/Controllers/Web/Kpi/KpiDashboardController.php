<?php

namespace App\Http\Controllers\Web\Kpi;

use App\Http\Controllers\Controller;
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
        ] + $this->filterOptions($filters));
    }

    /** Phase 5 placeholder: user KPIs wait for the kpi.users.view business decision. */
    public function users()
    {
        return view('pages.kpi.users');
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

    private function filterOptions(array $filters, ?array $moduleKeys = null): array
    {
        return [
            'years' => KpiPeriod::selectableYears(),
            'months' => KpiPeriod::MONTHS_AR,
            'branches' => $filters['scope']->availableBranches(),
            'moduleOptions' => collect($moduleKeys ?? array_keys($this->registry->modules()))
                ->mapWithKeys(fn (string $key): array => [$key => $this->registry->moduleLabel($key)])->all(),
        ];
    }

    /**
     * Server-side filter validation. Invalid values fall back to defaults and are reported;
     * a branch outside the viewer's scope is a 403 (KpiScope).
     *
     * @param  array<int, string>  $allowedModules  modules accepted by the "module" filter ([] = no module filter)
     */
    private function filters(Request $request, array $allowedModules, ?int $forcedBranchId = null): array
    {
        $rejected = [];
        $read = function (string $key, int $min, int $max) use ($request, &$rejected): ?int {
            $raw = $request->query($key);
            if ($raw === null || $raw === '') {
                return null;
            }
            $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
            if ($value === false) {
                $rejected[] = $key;

                return null;
            }

            return (int) $value;
        };

        $year = $read('year', KpiPeriod::MIN_YEAR, KpiPeriod::maxYear()) ?? (int) now()->year;
        $month = $read('month', 1, 12);
        $branchId = $forcedBranchId ?? $read('branch_id', 1, PHP_INT_MAX);

        $module = null;
        if ($allowedModules !== []) {
            $raw = $request->query('module');
            if ($raw !== null && $raw !== '') {
                if (is_string($raw) && in_array($raw, $allowedModules, true)) {
                    $module = $raw;
                } else {
                    $rejected[] = 'module';
                }
            }
        }

        return [
            'period' => new KpiPeriod($year, $month),
            'scope' => KpiScope::forUser($request->user(), $branchId),
            'year' => $year,
            'month' => $month,
            'branch_id' => $branchId,
            'module' => $module,
            'rejected' => $rejected,
            'query' => array_filter(['year' => $year, 'month' => $month, 'branch_id' => $branchId, 'module' => $module], fn ($value) => $value !== null),
        ];
    }
}
