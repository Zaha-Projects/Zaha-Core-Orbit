<?php

namespace App\Http\Controllers\Web\Kpi;

use App\Http\Controllers\Controller;
use App\Services\Kpi\KpiCalculatorService;
use App\Services\Kpi\KpiPeriod;
use App\Services\Kpi\KpiRegistry;
use App\Services\Kpi\KpiScope;
use Illuminate\Http\Request;

/**
 * KPI section (مؤشرات الأداء والتقارير). Access: role_or_permission:super_admin|kpi.view (route).
 * Branch scope is resolved by KpiScope for every page.
 */
class KpiDashboardController extends Controller
{
    private KpiRegistry $registry;
    private KpiCalculatorService $calculator;

    public function __construct(KpiRegistry $registry, KpiCalculatorService $calculator)
    {
        $this->registry = $registry;
        $this->calculator = $calculator;
    }

    public function overview(Request $request)
    {
        $filters = $this->filters($request, true);
        $moduleKeys = $filters['module'] ? [$filters['module']] : array_keys($this->registry->modules());

        return view('pages.kpi.overview', $this->viewData($filters, $moduleKeys, $filters['module']));
    }

    public function events(Request $request)
    {
        $filters = $this->filters($request, false);

        return view('pages.kpi.events', [
            'filters' => $filters,
            'modules' => $this->calculator->summaries(array_keys($this->registry->modules()), $filters['period'], $filters['scope']),
        ] + $this->filterOptions($filters));
    }

    public function event(Request $request, string $module)
    {
        abort_unless($this->registry->hasModule($module), 404);
        $filters = $this->filters($request, false);

        return view('pages.kpi.event', $this->viewData($filters, [$module], $module) + [
            'module' => $this->registry->module($module) + ['key' => $module],
        ]);
    }

    public function branches(Request $request)
    {
        $filters = $this->filters($request, true);
        $moduleKeys = $filters['module'] ? [$filters['module']] : array_keys($this->registry->modules());

        return view('pages.kpi.branches', [
            'filters' => $filters,
            'moduleKeys' => $moduleKeys,
            'modules' => $this->registry->modules(),
            'stages' => $this->registry->stages(),
            'rows' => $this->calculator->branchBreakdown($moduleKeys, $filters['period'], $filters['scope']),
        ] + $this->filterOptions($filters));
    }

    /** Phase 6 placeholder: user KPIs wait for the kpi.users.view business decision. */
    public function users()
    {
        return view('pages.kpi.users');
    }

    private function viewData(array $filters, array $moduleKeys, ?string $definitionModule): array
    {
        $data = $this->calculator->overview($moduleKeys, $filters['period'], $filters['scope'], $definitionModule);

        return [
            'filters' => $filters,
            'results' => $data['results'],
            'modules' => $data['modules'],
            'charts' => $data['charts'],
            'stages' => $this->registry->stages(),
        ] + $this->filterOptions($filters);
    }

    private function filterOptions(array $filters): array
    {
        return [
            'years' => KpiPeriod::selectableYears(),
            'months' => KpiPeriod::MONTHS_AR,
            'branches' => $filters['scope']->availableBranches(),
            'moduleOptions' => collect($this->registry->modules())->map(fn (array $module) => $module['label_ar'])->all(),
        ];
    }

    /**
     * Server-side filter validation. Invalid values fall back to defaults and are reported;
     * a branch outside the viewer's scope is a 403 (KpiScope).
     */
    private function filters(Request $request, bool $withModule): array
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
        $branchId = $read('branch_id', 1, PHP_INT_MAX);

        $module = null;
        if ($withModule) {
            $module = $request->query('module');
            $module = is_string($module) || $module === null ? $module : '#invalid';
            if ($module !== null && $module !== '' && ! $this->registry->hasModule($module)) {
                $rejected[] = 'module';
            }
            $module = $this->registry->hasModule($module) ? $module : null;
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
