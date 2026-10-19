<?php

use App\Services\Kpi\Modules\AgendaKpiDefinition;
use App\Services\Kpi\Modules\BazaarKpiDefinition;
use App\Services\Kpi\Modules\MonthlyActivityKpiDefinition;
use App\Services\Kpi\Modules\RamadanIftarKpiDefinition;

/*
|--------------------------------------------------------------------------
| KPI catalogue (canonical definitions)
|--------------------------------------------------------------------------
|
| Definitions only: no SQL and no formulas in Blade. Calculation is done by
| App\Services\Kpi\KpiCalculatorService using the module lifecycle definitions
| below. See docs/kpi-reporting-refactor-phase3-foundation.md.
|
| Targets: no business target has been approved yet (Phase 1 open decision).
| A KPI with a comparison operator but no approved target is marked
| configuration_required and never gets an achieved / not-achieved status.
|
*/

return [
    'modules' => [
        'agenda' => ['label_ar' => 'الأجندة السنوية', 'icon' => 'fa-calendar-days', 'accent' => 'agenda', 'definition' => AgendaKpiDefinition::class],
        'monthly' => ['label_ar' => 'الأنشطة الشهرية', 'icon' => 'fa-layer-group', 'accent' => 'monthly', 'definition' => MonthlyActivityKpiDefinition::class],
        'ramadan' => ['label_ar' => 'الإفطارات الرمضانية', 'icon' => 'fa-moon', 'accent' => 'ramadan', 'definition' => RamadanIftarKpiDefinition::class],
        'bazaar' => ['label_ar' => 'البازارات', 'icon' => 'fa-store', 'accent' => 'bazaar', 'definition' => BazaarKpiDefinition::class],
    ],

    'stages' => [
        'planned' => ['label_ar' => 'مخطط (مُقدَّم)', 'description_ar' => 'سجل حالي غادر مرحلة المسودة.'],
        'approved' => ['label_ar' => 'معتمد', 'description_ar' => 'وصل إلى الاعتماد النهائي أو ما بعده.'],
        'executed' => ['label_ar' => 'منفذ', 'description_ar' => 'سُجّل تنفيذه أو وصل إلى ما بعد التنفيذ.'],
        'closed' => ['label_ar' => 'مغلق', 'description_ar' => 'أُغلق نهائياً.'],
    ],

    'definitions' => [
        'EV-PLANNED' => [
            'name_ar' => 'الفعاليات المخططة',
            'name_en' => 'Planned events',
            'description_ar' => 'عدد السجلات الحالية التي غادرت مرحلة المسودة ضمن الفترة.',
            'family' => 'event',
            'category' => 'planning',
            'applicable_modules' => ['agenda', 'monthly', 'ramadan', 'bazaar'],
            'unit' => 'count',
            'comparison_operator' => null,
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_count', 'stage' => 'planned'],
            'icon' => 'fa-clipboard-list',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
        'EV-APPROVED' => [
            'name_ar' => 'الفعاليات المعتمدة',
            'name_en' => 'Approved events',
            'description_ar' => 'عدد الفعاليات التي وصلت إلى الاعتماد النهائي أو ما بعده.',
            'family' => 'event',
            'category' => 'approval',
            'applicable_modules' => ['agenda', 'monthly', 'ramadan', 'bazaar'],
            'unit' => 'count',
            'comparison_operator' => null,
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_count', 'stage' => 'approved'],
            'icon' => 'fa-circle-check',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
        'EV-EXECUTED' => [
            'name_ar' => 'الفعاليات المنفذة',
            'name_en' => 'Executed events',
            'description_ar' => 'عدد الفعاليات التي سُجّل تنفيذها. لا يشمل الأجندة السنوية لأنها لا تمتلك مرحلة تنفيذ.',
            'family' => 'event',
            'category' => 'execution',
            'applicable_modules' => ['monthly', 'ramadan', 'bazaar'],
            'unit' => 'count',
            'comparison_operator' => null,
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_count', 'stage' => 'executed'],
            'icon' => 'fa-person-running',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
        'EV-CLOSED' => [
            'name_ar' => 'الفعاليات المغلقة',
            'name_en' => 'Closed events',
            'description_ar' => 'عدد الفعاليات المغلقة نهائياً. لا يشمل الأجندة السنوية.',
            'family' => 'event',
            'category' => 'closure',
            'applicable_modules' => ['monthly', 'ramadan', 'bazaar'],
            'unit' => 'count',
            'comparison_operator' => null,
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_count', 'stage' => 'closed'],
            'icon' => 'fa-lock',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
        'EV-APPROVAL-RATE' => [
            'name_ar' => 'نسبة الاعتماد',
            'name_en' => 'Approval rate',
            'description_ar' => 'المعتمد ÷ المخطط ضمن الفترة.',
            'family' => 'event',
            'category' => 'approval',
            'applicable_modules' => ['agenda', 'monthly', 'ramadan', 'bazaar'],
            'unit' => 'percentage',
            'comparison_operator' => '>=',
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_rate', 'numerator' => 'approved', 'denominator' => 'planned'],
            'icon' => 'fa-check-double',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
        'EV-EXECUTION-RATE' => [
            'name_ar' => 'نسبة التنفيذ من المعتمد المستحق',
            'name_en' => 'Execution rate (due)',
            'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ موعده (تاريخه حتى اليوم) ضمن الفترة.',
            'family' => 'event',
            'category' => 'execution',
            'applicable_modules' => ['monthly', 'ramadan', 'bazaar'],
            'unit' => 'percentage',
            'comparison_operator' => '>=',
            'target' => null,
            'target_source' => 'none',
            'calculator' => ['type' => 'stage_rate', 'numerator' => 'executed_due', 'denominator' => 'approved_due'],
            'icon' => 'fa-chart-line',
            'drilldown' => null,
            'active' => true,
            'guide' => ['phase' => 8],
        ],
    ],
];
