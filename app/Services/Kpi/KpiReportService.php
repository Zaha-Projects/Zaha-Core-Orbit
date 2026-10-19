<?php

namespace App\Services\Kpi;

use App\Services\Kpi\Support\KpiSql;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * HTML drill-down reports behind KPI cards. Paginated, date-bounded and branch-scoped; the row
 * arithmetic (delay, achievement %) is done in SQL so Blade only prints values.
 * Exports are a later phase.
 */
class KpiReportService
{
    public const REPORTS = [
        'on-time' => ['title' => 'التنفيذ في الموعد', 'modules' => ['monthly', 'ramadan'], 'icon' => 'fa-clock'],
        'attendance' => ['title' => 'تحقيق الحضور', 'modules' => ['monthly', 'ramadan'], 'icon' => 'fa-people-group'],
        'ramadan-meals' => ['title' => 'الوجبات المخططة والفعلية', 'modules' => ['ramadan'], 'icon' => 'fa-utensils'],
        'bazaar-occupancy' => ['title' => 'إشغال طاولات البازار', 'modules' => ['bazaar'], 'icon' => 'fa-table-cells'],
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

    /** Validated module for a report (first supported module by default). */
    public static function moduleFor(string $report, $module): string
    {
        $modules = self::REPORTS[$report]['modules'];

        return is_string($module) && in_array($module, $modules, true) ? $module : $modules[0];
    }

    /**
     * @return array{title: string, icon: string, module: string, modules: array, columns: array<string, string>, rows: ?LengthAwarePaginator, notes: array}
     */
    public function build(string $report, string $module, KpiPeriod $period, KpiScope $scope): array
    {
        $meta = self::REPORTS[$report];
        $definition = $this->registry->moduleDefinition($module);
        [$columns, $selects, $condition, $order, $notes] = $this->spec($report, $module);

        $rows = null;
        if (! $scope->hasNoBranches()) {
            $query = $definition->liveQuery('s')
                ->leftJoin('branches as b', 'b.id', '=', 's.branch_id')
                ->whereBetween($definition->dateExpression('s'), [$period->startDate(), $period->endBound()])
                ->whereRaw($condition);
            $scopeSql = $definition->branchScopeSql('s', $scope->branchIds());
            if (is_string($scopeSql)) {
                $query->whereRaw($scopeSql);
            }
            foreach ($this->joins($report, $module) as [$table, $first, $second]) {
                $query->leftJoin($table, $first, '=', $second);
            }

            $rows = $query->selectRaw(implode(', ', $selects))
                ->orderByRaw($order)
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        }

        return [
            'title' => $meta['title'],
            'icon' => $meta['icon'],
            'module' => $module,
            'modules' => collect($meta['modules'])->mapWithKeys(fn (string $key): array => [$key => $this->registry->moduleLabel($key)])->all(),
            'columns' => $columns,
            'rows' => $rows,
            'notes' => $notes,
        ];
    }

    private function joins(string $report, string $module): array
    {
        if ($report === 'on-time') {
            return [$module === 'monthly' ? ['users as u', 'u.id', 's.created_by'] : ['users as u', 'u.id', 's.relations_officer_id']];
        }

        return [];
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
