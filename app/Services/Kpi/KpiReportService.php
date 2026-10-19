<?php

namespace App\Services\Kpi;

use App\Models\User;
use App\Services\Kpi\Support\KpiSql;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;

/**
 * Detailed KPI reports behind KPI cards: subject reports (Phase 4) and per-user action reports
 * (Phase 5). Query building is separate from pagination so the HTML page and the CSV export run
 * exactly the same date-bounded, branch-scoped (and user-filtered) query; Blade only prints
 * values and the CSV export streams the same rows with a cursor.
 */
class KpiReportService
{
    public const REPORTS = [
        'on-time' => ['title' => 'التنفيذ في الموعد', 'modules' => ['monthly', 'ramadan'], 'icon' => 'fa-clock'],
        'attendance' => ['title' => 'تحقيق الحضور', 'modules' => ['monthly', 'ramadan'], 'icon' => 'fa-people-group'],
        'ramadan-meals' => ['title' => 'الوجبات المخططة والفعلية', 'modules' => ['ramadan'], 'icon' => 'fa-utensils'],
        'bazaar-occupancy' => ['title' => 'إشغال طاولات البازار', 'modules' => ['bazaar'], 'icon' => 'fa-table-cells'],
    ];

    /**
     * Per-user action reports: action => title, actor sources per module, optional fixed row filter.
     * Every user KPI drills into one of these, so its value can be explained row by row.
     */
    public const USER_ACTIONS = [
        'created' => ['title' => 'الفعاليات المنشأة', 'icon' => 'fa-square-plus', 'sources' => ['monthly' => ['u_created'], 'ramadan' => ['u_created'], 'bazaar' => ['u_created'], 'agenda' => ['u_created']]],
        'submitted' => ['title' => 'سجلات التقديم', 'icon' => 'fa-paper-plane', 'sources' => ['monthly' => ['u_submissions'], 'ramadan' => ['u_submissions'], 'agenda' => ['u_submissions']]],
        'returned' => ['title' => 'الفعاليات المعادة للتصحيح', 'icon' => 'fa-rotate-left', 'filter' => 'returned', 'sources' => ['monthly' => ['u_created'], 'ramadan' => ['u_created'], 'agenda' => ['u_created']]],
        'post_execution' => ['title' => 'إدخالات ما بعد التنفيذ', 'icon' => 'fa-file-circle-check', 'sources' => ['monthly' => ['u_post_execution'], 'ramadan' => ['u_post_execution']]],
        'decisions' => ['title' => 'قرارات الاعتماد', 'icon' => 'fa-square-check', 'filters' => ['approved' => 'اعتماد', 'returned' => 'إعادة', 'rejected' => 'رفض'], 'sources' => ['monthly' => ['u_decisions'], 'ramadan' => ['u_decisions'], 'agenda' => ['u_decisions']]],
        'post_exec_reviews' => ['title' => 'مراجعات ما بعد التنفيذ', 'icon' => 'fa-file-circle-question', 'sources' => ['monthly' => ['u_post_execution_returns', 'u_closures'], 'ramadan' => ['u_monitoring_reviews']]],
        'closures' => ['title' => 'قرارات الإغلاق', 'icon' => 'fa-lock', 'sources' => ['monthly' => ['u_closures'], 'ramadan' => ['u_closures'], 'bazaar' => ['u_closures']]],
        'discounts' => ['title' => 'قرارات خصومات البازار', 'icon' => 'fa-percent', 'sources' => ['bazaar' => ['u_discounts']]],
        'verifications' => ['title' => 'بنود التحقق والمطابقة', 'icon' => 'fa-spell-check', 'sources' => ['monthly' => ['u_verifications'], 'ramadan' => ['u_matches'], 'bazaar' => ['u_verifications']]],
        'monitoring' => ['title' => 'حالات المتابعة المكتملة والمعادة', 'icon' => 'fa-clipboard-check', 'sources' => ['monthly' => ['u_evaluations'], 'ramadan' => ['u_monitoring', 'u_reports_returned'], 'bazaar' => ['u_verifications']]],
        'evaluations' => ['title' => 'التقييمات المقدّمة', 'icon' => 'fa-star', 'sources' => ['monthly' => ['u_evaluations']]],
    ];

    public const USER_ACTION_COLUMNS = [
        'user_name' => 'المستخدم',
        'module' => 'نوع الفعالية',
        'event' => 'الفعالية',
        'branch' => 'الفرع',
        'action' => 'الإجراء',
        'action_date' => 'التاريخ',
        'result' => 'النتيجة',
        'duration' => 'المدة',
        'duration_unit' => 'وحدة المدة',
    ];

    private const PER_PAGE = 25;

    private KpiRegistry $registry;

    public function __construct(KpiRegistry $registry)
    {
        $this->registry = $registry;
    }

    public static function exists(string $report): bool
    {
        return array_key_exists($report, self::REPORTS);
    }

    public static function userActionExists(string $action): bool
    {
        return array_key_exists($action, self::USER_ACTIONS);
    }

    /** Validated module for a report (first supported module by default). */
    public static function moduleFor(string $report, $module): string
    {
        $modules = self::REPORTS[$report]['modules'];

        return is_string($module) && in_array($module, $modules, true) ? $module : $modules[0];
    }

    public static function userActionModuleFor(string $action, $module): string
    {
        $modules = array_keys(self::USER_ACTIONS[$action]['sources']);

        return is_string($module) && in_array($module, $modules, true) ? $module : $modules[0];
    }

    public static function userActionFilterFor(string $action, $filter): ?string
    {
        return is_string($filter) && array_key_exists($filter, self::USER_ACTIONS[$action]['filters'] ?? []) ? $filter : null;
    }

    /* ------------------------------------------------------------------ subject reports (Phase 4) */

    /**
     * @return array{title: string, icon: string, module: string, modules: array, columns: array<string, string>, rows: ?LengthAwarePaginator, notes: array}
     */
    public function build(string $report, string $module, KpiPeriod $period, KpiScope $scope): array
    {
        $meta = self::REPORTS[$report];
        [$columns, , , , $notes] = $this->spec($report, $module);
        $query = $this->query($report, $module, $period, $scope);

        return [
            'title' => $meta['title'],
            'icon' => $meta['icon'],
            'module' => $module,
            'modules' => $this->moduleLabels($meta['modules']),
            'columns' => $columns,
            'rows' => $query ? $query->paginate(self::PER_PAGE)->withQueryString() : null,
            'notes' => $notes,
        ];
    }

    /** The report query (shared by HTML and CSV); null when the viewer has no branches. */
    public function query(string $report, string $module, KpiPeriod $period, KpiScope $scope): ?Builder
    {
        if ($scope->hasNoBranches()) {
            return null;
        }

        $definition = $this->registry->moduleDefinition($module);
        [, $selects, $condition, $order] = $this->spec($report, $module);
        $query = $definition->liveQuery('s')
            ->leftJoin('branches as b', 'b.id', '=', 's.branch_id')
            ->whereBetween($definition->dateExpression('s'), [$period->startDate(), $period->endBound()])
            ->whereRaw($condition);
        $scopeSql = $definition->branchScopeSql('s', $scope->branchIds());
        if (is_string($scopeSql)) {
            $query->whereRaw($scopeSql);
        }
        if ($report === 'on-time') {
            $query->leftJoin('users as u', 'u.id', '=', $module === 'monthly' ? 's.created_by' : 's.relations_officer_id');
        }

        return $query->selectRaw(implode(', ', $selects))->orderByRaw($order);
    }

    /** CSV header labels and row mapper for a subject report. */
    public function csvFor(string $report, string $module): array
    {
        [$columns] = $this->spec($report, $module);
        $mapper = function ($row) use ($columns): array {
            $values = [];
            foreach (array_keys($columns) as $column) {
                $values[] = $column === 'on_time' ? ((int) $row->on_time === 1 ? 'نعم' : 'لا') : ($row->{$column} ?? '');
            }

            return $values;
        };

        return [array_values($columns), $mapper];
    }

    /* ------------------------------------------------------------------ user action reports (Phase 5) */

    /**
     * @return array{title: string, icon: string, action: string, module: string, modules: array, filter: ?string, filters: array, columns: array, rows: ?LengthAwarePaginator}
     */
    public function userActions(string $action, string $module, ?string $filter, User $user, KpiPeriod $period, KpiScope $scope): array
    {
        $meta = self::USER_ACTIONS[$action];
        $query = $this->userActionsQuery($action, $module, $filter, $user, $period, $scope);

        return [
            'title' => $meta['title'],
            'icon' => $meta['icon'],
            'action' => $action,
            'module' => $module,
            'modules' => $this->moduleLabels(array_keys($meta['sources'])),
            'filter' => $filter,
            'filters' => $meta['filters'] ?? [],
            'columns' => self::USER_ACTION_COLUMNS,
            'rows' => $query ? $query->paginate(self::PER_PAGE)->withQueryString() : null,
        ];
    }

    /**
     * Rows of one user's actions: the same actor sources (base query, actor, date column, branch
     * scope) that feed the user KPIs, so every KPI value is explainable from these rows.
     */
    public function userActionsQuery(string $action, string $module, ?string $filter, User $user, KpiPeriod $period, KpiScope $scope): ?Builder
    {
        if ($scope->hasNoBranches()) {
            return null;
        }

        $definition = $this->registry->moduleDefinition($module);
        $filter = self::USER_ACTIONS[$action]['filter'] ?? $filter;
        $label = KpiSql::literal($this->registry->moduleLabel($module));
        $queries = [];

        foreach (self::USER_ACTIONS[$action]['sources'][$module] ?? [] as $key) {
            $source = $definition->metricSource($key);
            $built = $source ? $source->build($scope->branchIds(), false, [(int) $user->id]) : null;
            if ($built === null) {
                continue;
            }
            [$query] = $built;
            $query->whereBetween($source->dateExpression, [$period->startDate(), $period->endBound()])
                ->leftJoin('users as au', 'au.id', '=', $source->actor);
            if ($definition->hasBranchColumn()) {
                $query->leftJoin('branches as b', 'b.id', '=', 's.branch_id');
            }
            if ($filter !== null && isset($source->rowFilters[$filter])) {
                $query->whereRaw($source->rowFilters[$filter]);
            }

            $rows = $source->rows;
            $duration = $rows['duration_hours'] ?? $rows['duration_days'] ?? null;
            $unit = isset($rows['duration_hours']) ? "'ساعة'" : (isset($rows['duration_days']) ? "'يوم'" : 'NULL');
            $query->selectRaw(implode(', ', [
                'au.name AS user_name',
                $label.' AS module',
                $rows['event'].' AS event',
                ($definition->hasBranchColumn() ? 'b.name' : 'NULL').' AS branch',
                ($rows['action'] ?? 'NULL').' AS action',
                $rows['action_date'].' AS action_date',
                ($rows['result'] ?? 'NULL').' AS result',
                ($duration === null ? 'NULL' : 'ROUND('.$duration.', 1)').' AS duration',
                $unit.' AS duration_unit',
            ]));
            $queries[] = $query;
        }

        if ($queries === []) {
            return null;
        }

        $union = array_shift($queries);
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return $union->orderBy('action_date', 'desc');
    }

    public function userActionsCsvMapper(): callable
    {
        return function ($row): array {
            $values = [];
            foreach (array_keys(self::USER_ACTION_COLUMNS) as $column) {
                $values[] = $row->{$column} ?? '';
            }

            return $values;
        };
    }

    /* ------------------------------------------------------------------ helpers */

    private function moduleLabels(array $modules): array
    {
        return collect($modules)->mapWithKeys(fn (string $key): array => [$key => $this->registry->moduleLabel($key)])->all();
    }

    /** @return array{0: array, 1: array, 2: string, 3: string, 4: array} columns, selects, condition, order, notes */
    private function spec(string $report, string $module): array
    {
        $executed = $this->registry->moduleDefinition($module)->stageSql('executed', 's');

        switch ($report) {
            case 'on-time':
                $planned = $module === 'monthly' ? 'COALESCE(s.rescheduled_date, s.proposed_date)' : 's.planned_date';

                return [
                    ['event' => 'الفعالية', 'branch' => 'الفرع', 'planned_date' => 'التاريخ المخطط', 'actual_date' => 'التاريخ الفعلي', 'on_time' => 'في الموعد؟', 'delay_days' => 'التأخير (يوم)', 'responsible' => 'المسؤول'],
                    ['s.title AS event', 'b.name AS branch', $planned.' AS planned_date', 's.actual_date AS actual_date',
                        'CASE WHEN s.actual_date <= '.$planned.' THEN 1 ELSE 0 END AS on_time',
                        KpiSql::daysBetween($planned, 's.actual_date').' AS delay_days', 'u.name AS responsible'],
                    '('.$executed.') AND s.actual_date IS NOT NULL',
                    'delay_days DESC, planned_date ASC',
                    ['التاريخ الفعلي: actual_date (يوم دون وقت). التأخير السالب يعني تنفيذاً قبل الموعد.']
                        + ($module === 'monthly' ? [1 => 'التاريخ المخطط هو تاريخ إعادة الجدولة إن وُجد، وإلا التاريخ المقترح.'] : []),
                ];
            case 'attendance':
                $expected = $module === 'monthly' ? 'COALESCE(s.expected_attendance_to, s.expected_attendance)' : 's.expected_attendance';

                return [
                    ['event' => 'الفعالية', 'branch' => 'الفرع', 'planned' => 'الحضور المتوقع', 'actual' => 'الحضور الفعلي', 'achievement' => 'نسبة التحقيق %'],
                    ['s.title AS event', 'b.name AS branch', $expected.' AS planned', 's.actual_attendance AS actual',
                        'ROUND(s.actual_attendance * 100.0 / '.$expected.', 1) AS achievement'],
                    '('.$executed.') AND s.actual_attendance IS NOT NULL AND '.$expected.' > 0',
                    'achievement ASC',
                    ['تُعرض فقط الفعاليات المنفذة التي تملك حضوراً متوقعاً وفعلياً.'],
                ];
            case 'ramadan-meals':
                return [
                    ['event' => 'الإفطار', 'branch' => 'الفرع', 'planned' => 'الوجبات المخططة', 'actual' => 'الوجبات الفعلية', 'variance' => 'الفرق', 'achievement' => 'نسبة التحقيق %'],
                    ['s.title AS event', 'b.name AS branch', 's.planned_meals_count AS planned', 's.actual_meals_count AS actual',
                        '(s.actual_meals_count - s.planned_meals_count) AS variance', 'ROUND(s.actual_meals_count * 100.0 / s.planned_meals_count, 1) AS achievement'],
                    '('.$executed.') AND s.actual_meals_count IS NOT NULL AND s.planned_meals_count > 0',
                    'achievement ASC',
                    [],
                ];
            case 'bazaar-occupancy':
                return [
                    ['event' => 'البازار', 'branch' => 'الفرع', 'planned' => 'الطاولات المخططة', 'actual' => 'الطاولات المحجوزة', 'achievement' => 'نسبة الإشغال %'],
                    ['s.name AS event', 'b.name AS branch', 's.planned_table_count AS planned', 'COALESCE(s.actual_occupied_table_count, 0) AS actual',
                        'ROUND(COALESCE(s.actual_occupied_table_count, 0) * 100.0 / s.planned_table_count, 1) AS achievement'],
                    '('.$executed.') AND s.planned_table_count > 0',
                    'achievement ASC',
                    [],
                ];
        }

        abort(404);
    }
}
