<?php

namespace App\Services\Kpi;

use App\Models\Role;
use App\Services\Kpi\Support\KpiMetricSource;

/**
 * Builds the KPI Guide (دليل مؤشرات الأداء) entirely from config/kpis.php and the module metric
 * sources: formulas from the calculator + metric labels, data sources from the actual source
 * queries (tables, timestamp column, actor), statuses from availability. Nothing is duplicated
 * by hand. Worked examples are illustrative numbers, never production values.
 */
class KpiGuideService
{
    private const PAGE_LABELS = [
        'overview' => 'نظرة عامة (عبر الفعاليات)',
        'module' => 'مؤشرات الوحدة',
        'branch' => 'مؤشرات الفروع',
        'user' => 'أداء المستخدمين',
    ];

    private const UNIT_LABELS = [
        'percentage' => 'نسبة مئوية', 'count' => 'عدد', 'count_decimal' => 'عدد (متوسط)', 'days' => 'أيام',
        'hours' => 'ساعات', 'score' => 'درجة من 10', 'currency' => 'دينار أردني',
    ];

    private KpiRegistry $registry;
    private KpiFormatter $formatter;

    public function __construct(KpiRegistry $registry, KpiFormatter $formatter)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
    }

    /** @return array{entries: array, filters: array, counts: array} */
    public function build(): array
    {
        $roleLabels = Role::query()->get()->mapWithKeys(fn (Role $role): array => [$role->name => $role->display_name ?? $role->name])->all();
        $families = (array) config('kpis.user_families', []);
        $entries = [];

        foreach ($this->registry->definitions() as $code => $definition) {
            $entries[] = $this->entry($code, $definition, $families, $roleLabels);
        }

        $statuses = $this->registry->statuses();

        return [
            'entries' => $entries,
            'filters' => [
                'modules' => collect($this->registry->modules())->map(fn (array $module) => $module['label_ar'])->all(),
                'categories' => $this->registry->categories(),
                'pages' => self::PAGE_LABELS,
                'families' => collect($families)->map(fn (array $family) => $family['label_ar'])->all(),
                'statuses' => collect($statuses)->map(fn (array $status) => $status['label_ar'])->all(),
            ],
            'counts' => collect($entries)->countBy('status')->all(),
        ];
    }

    private function entry(string $code, array $definition, array $families, array $roleLabels): array
    {
        $calculator = $definition['calculator'] ?? null;
        $family = $definition['user_family'] ?? null;
        $modules = $this->moduleRows($definition, $calculator);
        $status = $this->status($definition, $modules);

        return [
            'code' => $code,
            'aliases' => $definition['aliases'] ?? [],
            'name' => $definition['name_ar'],
            'description' => $definition['description_ar'],
            'icon' => $definition['icon'],
            'page' => $definition['page'],
            'page_label' => self::PAGE_LABELS[$definition['page']] ?? $definition['page'],
            'category' => $definition['category'],
            'category_label' => $this->registry->categories()[$definition['category']] ?? $definition['category'],
            'family' => $family,
            'family_label' => $family ? ($families[$family]['label_ar'] ?? $family) : null,
            'roles' => $family
                ? array_map(fn (string $role): string => $roleLabels[$role] ?? $role, $families[$family]['roles'] ?? [])
                : ['أصحاب صلاحية عرض المؤشرات (kpi.view) ضمن نطاق فروعهم'],
            'unit' => self::UNIT_LABELS[$definition['unit']] ?? $definition['unit'],
            'formula' => $calculator ? $this->formula($calculator, $definition['unit']) : null,
            'numerator' => $calculator && ! empty($calculator['numerator']) ? $this->metricLabel($calculator['numerator']) : null,
            'denominator' => $calculator && ! empty($calculator['denominator']) ? $this->metricLabel($calculator['denominator']) : null,
            'example' => $calculator ? $this->example($calculator, $definition['unit']) : null,
            'target' => $this->targetText($definition),
            'proposed_target' => $this->proposedTargetText($definition),
            'how_to_read' => $this->howToRead($definition),
            'status' => $status,
            'modules' => $modules,
            'module_keys' => array_keys(array_filter($modules, fn (array $row): bool => in_array($row['status'], ['READY', 'PARTIAL'], true))),
            'why_unavailable' => $this->whyUnavailable($definition, $modules),
            'limitations' => $definition['limitations'] ?? [],
            'drilldown' => $this->drilldownLabel($definition),
        ];
    }

    /** Per-module status, formula overrides and real data source (tables, timestamp, actor). */
    private function moduleRows(array $definition, ?array $calculator): array
    {
        $rows = [];
        foreach ($this->registry->modules() as $key => $module) {
            if ($definition['unavailable_reason'] ?? null) {
                $rows[$key] = ['label' => $module['label_ar'], 'status' => 'UNAVAILABLE', 'reason' => $definition['unavailable_reason'], 'sources' => [], 'formula' => null];
                continue;
            }
            if (isset($definition['availability'][$key]) && $calculator) {
                $spec = $definition['availability'][$key];
                $spec = is_array($spec) ? $spec : ['status' => $spec];
                $metrics = array_filter([$spec['numerator'] ?? $calculator['numerator'] ?? null, $spec['denominator'] ?? $calculator['denominator'] ?? null]);
                $override = isset($spec['numerator']) || isset($spec['denominator'])
                    ? $this->formula(['numerator' => $spec['numerator'] ?? $calculator['numerator'], 'denominator' => $spec['denominator'] ?? ($calculator['denominator'] ?? null)] + $calculator, $definition['unit'])
                    : null;
                $rows[$key] = ['label' => $module['label_ar'], 'status' => $spec['status'], 'reason' => null, 'sources' => $this->sources($key, $metrics), 'formula' => $override];
            } elseif (isset($definition['not_applicable'][$key])) {
                $rows[$key] = ['label' => $module['label_ar'], 'status' => 'NOT_APPLICABLE', 'reason' => $definition['not_applicable'][$key], 'sources' => [], 'formula' => null];
            } elseif (isset($definition['unavailable'][$key])) {
                $rows[$key] = ['label' => $module['label_ar'], 'status' => 'UNAVAILABLE', 'reason' => $definition['unavailable'][$key], 'sources' => [], 'formula' => null];
            }
        }

        return $rows;
    }

    /** Describe the metric sources that compute the given metrics for a module. */
    private function sources(string $module, array $metrics): array
    {
        if (in_array('pending_workload', $metrics, true)) {
            return [['tables' => 'workflow_instances ⋈ workflow_steps (الخطوة الحالية) ⋈ جدول الوحدة', 'timestamp' => 'لقطة حالية (لا فترة)', 'actor' => 'أدوار المستخدم (workflow_steps.role_id)']];
        }

        $rows = [];
        foreach ($this->registry->moduleDefinition($module)->metricSources() as $source) {
            if (array_intersect($metrics, array_keys(array_filter($source->metrics, static fn ($sql) => $sql !== null))) === []) {
                continue;
            }
            $rows[$source->key] = [
                'tables' => $this->tables($source),
                'timestamp' => $source->dateExpression,
                'actor' => $source->actor,
            ];
        }

        return array_values($rows);
    }

    /** Tables read by a source, taken from its query builder (from + joins). */
    private function tables(KpiMetricSource $source): string
    {
        $built = $source->build(null, false);
        if ($built === null) {
            return '—';
        }
        [$query] = $built;
        $tables = [(string) $query->from];
        foreach ((array) $query->joins as $join) {
            $tables[] = (string) $join->table;
        }

        return implode(' ⋈ ', array_map(static fn (string $table): string => preg_replace('/\s+as\s+\w+$/i', '', $table), $tables));
    }

    private function status(array $definition, array $modules): string
    {
        if ($definition['unavailable_reason'] ?? null) {
            return 'UNAVAILABLE';
        }
        $statuses = array_column($modules, 'status');
        if (in_array('PARTIAL', $statuses, true)) {
            return 'PARTIAL';
        }
        if (in_array('READY', $statuses, true)) {
            return 'READY';
        }

        return in_array('UNAVAILABLE', $statuses, true) ? 'UNAVAILABLE' : 'NOT_APPLICABLE';
    }

    private function formula(array $calculator, string $unit): string
    {
        $numerator = $this->metricLabel($calculator['numerator'] ?? '');
        $denominator = isset($calculator['denominator']) ? $this->metricLabel($calculator['denominator']) : null;

        switch ($calculator['type']) {
            case 'ratio':
                $scale = (float) ($calculator['scale'] ?? 100);
                if (abs($scale - 100) < 0.0001) {
                    return $numerator.' ÷ '.$denominator.' × 100';
                }
                if (abs($scale - 1 / 24) < 0.0001) {
                    return '('.$numerator.' ÷ '.$denominator.') ÷ 24 (تحويل الساعات إلى أيام)';
                }

                return $numerator.' ÷ '.$denominator.' (متوسط)';
            case 'per_month':
                return $numerator.' ÷ عدد الأشهر المنقضية من الفترة';
            case 'snapshot':
                return 'عدد '.$numerator.' في هذه اللحظة';
            default:
                return 'مجموع: '.$numerator;
        }
    }

    /** Illustrative worked example derived from the formula shape (not production data). */
    private function example(array $calculator, string $unit): array
    {
        $numerator = $this->metricLabel($calculator['numerator'] ?? '');
        $denominator = isset($calculator['denominator']) ? $this->metricLabel($calculator['denominator']) : null;

        switch ($calculator['type']) {
            case 'ratio':
                $scale = (float) ($calculator['scale'] ?? 100);
                if (abs($scale - 100) < 0.0001) {
                    [$n, $d] = [8, 10];
                } elseif (abs($scale - 1 / 24) < 0.0001) {
                    [$n, $d] = [240, 5];
                } else {
                    [$n, $d] = [36, 12];
                }
                $value = $n / $d * $scale;

                return ['inputs' => [$numerator => $n, $denominator => $d], 'result' => $this->formatter->format(round($value, 2), $unit)];
            case 'per_month':
                return ['inputs' => [$numerator => 24, 'الأشهر المنقضية' => 6], 'result' => $this->formatter->format(4, $unit)];
            case 'snapshot':
                return ['inputs' => [$numerator => 7], 'result' => $this->formatter->format(7, $unit)];
            default:
                return ['inputs' => [$numerator => 12], 'result' => $this->formatter->format(12, $unit)];
        }
    }

    private function targetText(array $definition): string
    {
        if (($definition['target'] ?? null) !== null && ($definition['target_source'] ?? 'none') === 'configured') {
            return $definition['comparison_operator'].' '.$this->formatter->format($definition['target'], $definition['unit']).' (معتمد)';
        }

        return $definition['comparison_operator'] === null
            ? 'مؤشر وصفي بلا مستهدف؛ لا يُحكم عليه بالتحقيق.'
            : 'لم يتم تحديد المستهدف بعد؛ لا تُعرض حالة «محقق / غير محقق».';
    }

    private function proposedTargetText(array $definition): ?string
    {
        $proposed = $definition['proposed_target'] ?? null;
        if (! is_array($proposed)) {
            return null;
        }

        return $proposed['operator'].' '.$this->formatter->format($proposed['value'], $definition['unit']).' — مقترح غير معتمد، للاطلاع فقط ولا يُستخدم في التقييم.';
    }

    private function howToRead(array $definition): string
    {
        switch ($definition['comparison_operator']) {
            case '>=':
                return 'كلما ارتفعت القيمة كان الأداء أفضل. قارن بالفترة السابقة وبالفروع/المستخدمين من النوع نفسه.';
            case '<=':
                return 'كلما انخفضت القيمة كان الأداء أفضل (زمن أقل أو إعادات أقل). راجع التفاصيل لمعرفة الحالات المتأخرة.';
            case '=':
                return 'المطلوب أن تكون القيمة مطابقة تماماً (مثل 100%). أي انحراف يستحق المراجعة.';
        }

        return 'مؤشر وصفي يقيس الحجم أو النشاط؛ يُقرأ في سياق الفترة والنطاق، ولا يدل وحده على جودة الأداء.';
    }

    private function whyUnavailable(array $definition, array $modules): array
    {
        $reasons = [];
        if ($definition['unavailable_reason'] ?? null) {
            $reasons[] = $definition['unavailable_reason'];
        }
        foreach ($modules as $row) {
            if ($row['reason'] && ! in_array($row['reason'], $reasons, true)) {
                $reasons[] = $row['label'].': '.$row['reason'];
            }
        }
        $reasons[] = 'لا توجد سجلات في الفترة المختارة، أو المقام صفر (لا تُعرض قيمة 0 بديلة).';
        $reasons[] = 'حسابك غير مرتبط بفرع، أو الفرع المختار خارج نطاق صلاحياتك.';

        return $reasons;
    }

    private function drilldownLabel(array $definition): ?string
    {
        $drilldown = $definition['drilldown'] ?? null;
        if (! $drilldown) {
            return null;
        }
        if (($definition['page'] ?? null) === 'user') {
            return 'تقرير الإجراءات: '.(KpiReportService::USER_ACTIONS[$drilldown['action']]['title'] ?? $drilldown['action']);
        }

        return 'تقرير تفصيلي: '.(KpiReportService::REPORTS[$drilldown['report']]['title'] ?? $drilldown['report']);
    }

    private function metricLabel(string $metric): string
    {
        return (string) (config('kpis.metric_labels.'.$metric) ?? $metric);
    }
}
