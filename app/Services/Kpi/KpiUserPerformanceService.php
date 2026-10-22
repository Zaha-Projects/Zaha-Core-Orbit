<?php

namespace App\Services\Kpi;

use App\Models\User;
use App\Services\Kpi\Support\KpiWorkflowIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * User-performance views built on the shared KPI engine (actor sources + user grouping).
 * One payload shape serves My Profile, the admin user page and /dashboard/kpi/users/{user}.
 *
 * No universal score: results are shown KPI by KPI, grouped by role family.
 */
class KpiUserPerformanceService
{
    private KpiRegistry $registry;
    private KpiCalculatorService $calculator;
    private KpiFormatter $formatter;

    public function __construct(KpiRegistry $registry, KpiCalculatorService $calculator, KpiFormatter $formatter)
    {
        $this->registry = $registry;
        $this->calculator = $calculator;
        $this->formatter = $formatter;
    }

    /**
     * View data for the shared «الأداء» panel (My Profile, admin user page). Returns null when the
     * viewer may not see the target. Self: own actions in every branch; others: the viewer's scope.
     * Period comes from the request (year/month), validated like the KPI filters.
     */
    public function panel(User $viewer, User $target, Request $request): ?array
    {
        if (! KpiScope::canViewUser($viewer, $target)) {
            return null;
        }

        $isSelf = (int) $viewer->id === (int) $target->id;
        $read = static function (string $key, int $min, int $max) use ($request): ?int {
            $value = filter_var($request->query($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

            return $value === false || $value === null ? null : (int) $value;
        };
        $year = $read('year', KpiPeriod::MIN_YEAR, KpiPeriod::maxYear()) ?? (int) now()->year;
        $month = $read('month', 1, 12);
        $scope = $isSelf ? KpiScope::forSelf($viewer) : KpiScope::forUser($viewer, null);
        $period = new KpiPeriod($year, $month);

        return [
            'performance' => $this->performance($target, $period, $scope),
            'filters' => [
                'period' => $period, 'scope' => $scope, 'year' => $year, 'month' => $month, 'branch_id' => null, 'module' => null, 'rejected' => [],
                'query' => array_filter(['year' => $year, 'month' => $month], fn ($value) => $value !== null),
            ],
            'years' => KpiPeriod::selectableYears(),
            'months' => KpiPeriod::MONTHS_AR,
            'actionsRoute' => $isSelf ? 'profile.performance.actions' : 'kpi.users.actions',
            'actionsParameters' => $isSelf ? [] : ['user' => $target->id],
        ];
    }

    /** @return array<string, array> family key => family config, for the families the user's roles map to */
    public function familiesFor(User $user): array
    {
        $roles = $user->getRoleNames()->all();

        return array_filter($this->families(), fn (array $family): bool => array_intersect($family['roles'], $roles) !== []);
    }

    public function families(): array
    {
        return (array) config('kpis.user_families', []);
    }

    /** User KPI definitions of one family. */
    public function definitionsFor(string $family): array
    {
        return array_filter($this->registry->definitions('user'), fn (array $definition): bool => ($definition['user_family'] ?? null) === $family);
    }

    /**
     * Performance payload for one user. $scope is the VIEWER's scope (self: all branches), so a
     * manager never sees the user's actions in branches outside their own scope.
     */
    public function performance(User $user, KpiPeriod $period, KpiScope $scope): array
    {
        $families = $this->familiesFor($user);
        $modules = array_keys($this->registry->modules());
        $definitions = [];
        foreach (array_keys($families) as $family) {
            $definitions += $this->definitionsFor($family);
        }

        $regular = array_filter($definitions, fn (array $definition): bool => ($definition['calculator']['type'] ?? null) !== 'snapshot');
        $primaryMetrics = array_values(array_unique(array_column($families, 'primary')));
        $turnaroundDefinitions = array_filter(array_map(fn (array $family): ?array => $this->registry->definition($family['turnaround']), $families));

        $periodData = $this->calculator->dataset($modules, KpiCalculatorService::GROUP_PERIOD, $period, $scope,
            array_merge($this->registry->metricsFor($regular), $primaryMetrics), [(int) $user->id]);
        $monthData = $this->calculator->dataset($modules, KpiCalculatorService::GROUP_MONTH, $period, $scope,
            array_merge($primaryMetrics, $this->registry->metricsFor($turnaroundDefinitions)), [(int) $user->id]);

        if (array_filter($definitions, fn (array $definition): bool => ($definition['calculator']['type'] ?? null) === 'snapshot') !== []) {
            $periodData = $this->injectWorkload($periodData, $user, $scope);
        }

        $results = $this->calculator->results($definitions, $modules, $period, $scope, $periodData);

        $sections = [];
        foreach ($families as $key => $family) {
            $familyResults = array_intersect_key($results, $this->definitionsFor($key));
            $sections[$key] = [
                'key' => $key,
                'label' => $family['label_ar'],
                'icon' => $family['icon'],
                'reports' => $family['reports'],
                'results' => array_filter($familyResults, fn (KpiResult $result): bool => ! $this->isUnavailable($result)),
                'unavailable' => array_filter($familyResults, fn (KpiResult $result): bool => $this->isUnavailable($result)),
            ];
        }

        return [
            'user' => $user,
            'roles' => $user->roles->map(fn ($role) => $role->display_name ?? $role->name)->all(),
            'branch' => optional($user->branch)->name,
            'period' => $period,
            'sections' => $sections,
            'charts' => $families === [] ? [] : $this->charts($families, $modules, $periodData, $monthData, $period),
            'scope_note' => $scope->isAllBranches() ? null : 'تُعرض إجراءات المستخدم في الفروع ضمن نطاق صلاحياتك فقط.',
        ];
    }

    /**
     * Same-role comparison rows for the users page (one grouped query per actor source).
     *
     * @return array{users: Collection, rows: Collection, columns: array, charts: array}
     */
    public function comparison(string $family, KpiPeriod $period, KpiScope $scope, ?int $userFilter, string $sort, string $direction, ?array $modules = null): array
    {
        $config = $this->families()[$family];
        $modules = $modules ?? array_keys($this->registry->modules());
        $columns = [];
        foreach ($config['comparison'] as $code) {
            if ($definition = $this->registry->definition($code)) {
                $columns[$code] = $definition;
            }
        }

        $users = $this->candidates($config['roles'], $scope, $userFilter);
        $ids = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $data = $this->calculator->dataset($modules, KpiCalculatorService::GROUP_USER, $period, $scope,
            array_merge($this->registry->metricsFor($columns), [$config['primary']]), $ids);

        $rows = $users->map(function (User $user) use ($columns, $modules, $data, $period, $config): array {
            $values = [];
            foreach ($columns as $code => $definition) {
                $values[$code] = $this->calculator->valueFor($definition, $modules, $data, (string) $user->id, $period);
            }
            $primary = 0.0;
            foreach ($modules as $module) {
                $primary += (float) $this->calculator->metric($data, $module, (string) $user->id, $config['primary']);
            }

            return ['user_id' => (int) $user->id, 'user' => $user->name, 'branch' => optional($user->branch)->name, 'values' => $values, 'primary' => $primary];
        });

        $rows = $rows->sort(function (array $left, array $right) use ($sort, $direction): int {
            if ($sort === 'user') {
                $result = strcmp($left['user'], $right['user']);
            } else {
                $a = $left['values'][$sort]['value'] ?? null;
                $b = $right['values'][$sort]['value'] ?? null;
                if ($a === null || $b === null) {
                    return $a === $b ? 0 : ($a === null ? 1 : -1);
                }
                $result = $a <=> $b;
            }

            return $direction === 'desc' ? -$result : $result;
        })->values();

        return [
            'users' => $users,
            'rows' => $rows,
            'columns' => $columns,
            'charts' => $this->comparisonCharts($config, $columns, $rows, $modules, $period, $scope, $ids),
        ];
    }

    /** Users holding the family's roles inside the viewer's branch scope (and optional filters). */
    public function candidates(array $roles, KpiScope $scope, ?int $userFilter = null): Collection
    {
        $branchIds = $scope->branchIds();

        return User::query()
            ->with('branch:id,name')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))
            ->when($branchIds !== null, function ($query) use ($branchIds): void {
                $ids = $branchIds ?: [0];
                $query->where(function ($inScope) use ($ids): void {
                    $inScope->whereIn('branch_id', $ids)
                        ->orWhereHas('assignedBranches', fn ($assigned) => $assigned->whereIn('branches.id', $ids));
                });
            })
            ->when($userFilter !== null, fn ($query) => $query->whereKey($userFilter))
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);
    }

    /* ------------------------------------------------------------------ helpers */

    /**
     * MG-05 snapshot: workflow instances currently waiting at a main step assigned to one of the
     * user's roles, limited to subjects inside the viewer's scope. Steps assigned by permission
     * (no role_id) are not counted.
     */
    private function injectWorkload(array $data, User $user, KpiScope $scope): array
    {
        $roleIds = $user->roles->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach (['monthly', 'ramadan', 'agenda'] as $module) {
            if ($data['scoped_out'] || $roleIds === []) {
                continue;
            }
            $definition = $this->registry->moduleDefinition($module);
            $query = DB::table('workflow_instances as wi')
                ->join('workflow_steps as st', 'st.id', '=', 'wi.current_step_id')
                ->join($definition->table().' as s', 's.id', '=', 'wi.entity_id')
                ->whereRaw('wi.entity_type IN ('.KpiWorkflowIdentity::sqlList($module).')')
                ->whereIn('wi.status', ['pending', 'in_progress'])
                ->where('st.step_type', 'main')
                ->whereIn('st.role_id', $roleIds)
                ->whereRaw($definition->liveSql('s'));
            $scopeSql = $definition->branchScopeSql('s', $scope->branchIds());
            if (is_string($scopeSql)) {
                $query->whereRaw($scopeSql);
            }

            $data['defined'][$module]['pending_workload'] = true;
            $data['values'][$module]['current']['pending_workload'] = (float) $query->count();
        }

        return $data;
    }

    private function charts(array $families, array $modules, array $periodData, array $monthData, KpiPeriod $period): array
    {
        $months = array_values(KpiPeriod::MONTHS_AR);
        $monthly = [];
        $byModule = [];
        $turnaround = [];
        $hasMonthly = false;
        $hasModules = false;
        $hasTurnaround = false;

        foreach ($families as $key => $family) {
            $series = [];
            foreach (range(1, 12) as $month) {
                $total = null;
                foreach ($modules as $module) {
                    $value = $this->calculator->metric($monthData, $module, (string) $month, $family['primary']);
                    $total = $value === null ? $total : (float) $total + $value;
                }
                $series[] = $total;
                $hasMonthly = $hasMonthly || ($total ?? 0) > 0;
            }
            $monthly[] = ['label' => $family['primary_label'], 'data' => $series, 'color' => 'stage-'.['ro' => 'planned', 'sv' => 'approved', 'fo' => 'executed', 'mg' => 'closed'][$key]];

            $moduleValues = array_map(fn (string $module) => $this->calculator->metric($periodData, $module, 'current', $family['primary']), $modules);
            $hasModules = $hasModules || array_sum(array_map('floatval', $moduleValues)) > 0;
            $byModule[] = ['label' => $family['primary_label'], 'data' => $moduleValues, 'colors' => $modules];

            if ($definition = $this->registry->definition($family['turnaround'])) {
                $values = [];
                foreach (range(1, 12) as $month) {
                    $values[] = $this->calculator->valueFor($definition, $modules, $monthData, (string) $month, $period)['value'];
                }
                $hasTurnaround = $hasTurnaround || array_filter($values, static fn ($value) => $value !== null) !== [];
                $turnaround[] = ['label' => $definition['name_ar'], 'data' => $values, 'color' => 'stage-executed'];
            }
        }

        return [
            ['id' => 'userMonthlyActions', 'title' => 'الإجراءات شهرياً', 'description' => 'الإجراء الرئيسي لكل دور في كل شهر من سنة '.$period->year.'.', 'icon' => 'fa-chart-line', 'type' => 'line', 'labels' => $months, 'datasets' => $monthly, 'available' => $hasMonthly, 'unit' => null],
            ['id' => 'userModules', 'title' => 'التوزيع حسب نوع الفعالية', 'description' => 'الإجراء الرئيسي ضمن الفترة لكل نوع فعالية.', 'icon' => 'fa-layer-group', 'type' => 'hbar',
                'labels' => array_map(fn (string $module): string => $this->registry->moduleLabel($module), $modules), 'datasets' => $byModule, 'available' => $hasModules, 'unit' => null],
            ['id' => 'userTurnaround', 'title' => 'اتجاه زمن الإنجاز', 'description' => 'متوسط زمن الإنجاز لكل شهر (حسب مؤشر الزمن الخاص بكل دور).', 'icon' => 'fa-stopwatch', 'type' => 'line', 'labels' => $months, 'datasets' => $turnaround, 'available' => $hasTurnaround, 'unit' => null],
        ];
    }

    private function comparisonCharts(array $config, array $columns, Collection $rows, array $modules, KpiPeriod $period, KpiScope $scope, array $ids): array
    {
        $first = array_key_first($columns);
        $top = $rows->filter(fn (array $row): bool => $row['values'][$first]['value'] !== null)->sortByDesc(fn (array $row) => $row['values'][$first]['value'])->take(20)->values();
        $workload = $rows->sortByDesc('primary')->take(20)->values();

        $turnaround = $this->registry->definition($config['turnaround']);
        $monthData = $this->calculator->dataset($modules, KpiCalculatorService::GROUP_MONTH, $period, $scope, $this->registry->metricsFor([$turnaround]), $ids);
        $trend = [];
        foreach (range(1, 12) as $month) {
            $trend[] = $this->calculator->valueFor($turnaround, $modules, $monthData, (string) $month, $period)['value'];
        }

        return [
            ['id' => 'usersCompare', 'title' => 'مقارنة: '.$columns[$first]['name_ar'], 'description' => 'مقارنة المستخدمين من الدور نفسه في مؤشر واحد (بدون ترتيب أو درجة إجمالية).', 'icon' => 'fa-chart-column', 'type' => 'hbar',
                'labels' => $top->pluck('user')->all(), 'datasets' => [['label' => $columns[$first]['name_ar'], 'data' => $top->map(fn (array $row) => $row['values'][$first]['value'])->all(), 'color' => 'stage-approved']],
                'available' => $top->isNotEmpty(), 'unit' => $columns[$first]['unit'] === 'percentage' ? '%' : null],
            ['id' => 'usersWorkload', 'title' => 'توزيع عبء العمل: '.$config['primary_label'], 'description' => 'عدد الإجراءات الرئيسية لكل مستخدم ضمن الفترة.', 'icon' => 'fa-scale-unbalanced', 'type' => 'hbar',
                'labels' => $workload->pluck('user')->all(), 'datasets' => [['label' => $config['primary_label'], 'data' => $workload->pluck('primary')->all(), 'color' => 'stage-planned']],
                'available' => $workload->contains(fn (array $row): bool => $row['primary'] > 0), 'unit' => null],
            ['id' => 'usersTurnaround', 'title' => 'اتجاه '.$turnaround['name_ar'], 'description' => 'لكل المستخدمين المعروضين، لكل شهر من سنة '.$period->year.'.', 'icon' => 'fa-stopwatch', 'type' => 'line',
                'labels' => array_values(KpiPeriod::MONTHS_AR), 'datasets' => [['label' => $turnaround['name_ar'], 'data' => $trend, 'color' => 'stage-executed']],
                'available' => array_filter($trend, static fn ($value) => $value !== null) !== [], 'unit' => null],
        ];
    }

    private function isUnavailable(KpiResult $result): bool
    {
        return in_array($result->dataStatus, [KpiResult::DATA_UNAVAILABLE, KpiResult::DATA_NOT_APPLICABLE], true);
    }
}
