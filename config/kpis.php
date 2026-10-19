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
| Definitions only. Metrics (numerator / denominator names) are SQL aggregates owned by the
| module definitions in app/Services/Kpi/Modules; KpiCalculatorService combines them.
| See docs/kpi-reporting-refactor-phase4-module-branch-kpis.md.
|
| page:          overview | module | branch
| calculator:    sum (Σ numerator) | ratio (Σ numerator ÷ Σ denominator × scale) | per_month
| availability:  module => READY|PARTIAL, or [status, numerator, denominator] overrides
| not_applicable / unavailable: module => Arabic reason shown to management
| unavailable_reason: the whole KPI cannot be computed from current data
|
| Targets: no business target is approved (Phase 1 open decision). A KPI with a comparison
| operator and no approved target is "configuration required" and is never evaluated.
| Codes are stable: AG-/MA-/RI-/BZ- come from the Phase 1 audit; BR- replaces the Phase 1
| proposal codes B-X- (kept in "aliases"); EV- are the Phase 3 overview codes.
|
*/

$kpi = static function (array $definition): array {
    return $definition + [
        'name_en' => null,
        'family' => 'event',
        'unit' => 'percentage',
        'comparison_operator' => null,
        'target' => null,
        'target_source' => 'none',
        'availability' => [],
        'not_applicable' => [],
        'unavailable' => [],
        'unavailable_reason' => null,
        // module => modules that, when also selected, exclude it (prevents Agenda + Monthly double counting)
        'exclusive_with' => [],
        'limitations' => [],
        'aliases' => [],
        'icon' => 'fa-chart-simple',
        'drilldown' => null,
        'active' => true,
        'guide' => ['phase' => 8],
    ];
};
$ratio = static fn (string $numerator, string $denominator, float $scale = 100): array => ['type' => 'ratio', 'numerator' => $numerator, 'denominator' => $denominator, 'scale' => $scale];
$sum = static fn (string $numerator): array => ['type' => 'sum', 'numerator' => $numerator];
$hoursAsDays = 1 / 24;
$agendaNa = ['agenda' => 'الأجندة السنوية لا تمتلك مرحلة تنفيذ؛ يتم التنفيذ عبر الأنشطة الشهرية المرتبطة.'];
$branchModulesNa = ['agenda' => 'الأجندة تخطيط مركزي؛ تنفيذ الفرع يُحتسب عبر نشاطه الشهري المرتبط لتجنب العد المزدوج.'];
$bazaarNoActualDate = ['bazaar' => 'لا يُسجَّل تاريخ تنفيذ فعلي للبازار؛ تاريخ البازار هو التاريخ المخطط.'];
$bazaarNoAttendance = ['bazaar' => 'لا يُسجّل حضور للبازار.'];
$targetNote = 'المستهدف غير معتمد بعد.';
$agendaExclusive = ['agenda' => ['monthly']];

return [
    'modules' => [
        'agenda' => ['label_ar' => 'الأجندة السنوية', 'icon' => 'fa-calendar-days', 'accent' => 'agenda', 'definition' => AgendaKpiDefinition::class],
        'monthly' => ['label_ar' => 'الأنشطة الشهرية', 'icon' => 'fa-layer-group', 'accent' => 'monthly', 'definition' => MonthlyActivityKpiDefinition::class],
        'ramadan' => ['label_ar' => 'الإفطارات الرمضانية', 'icon' => 'fa-moon', 'accent' => 'ramadan', 'definition' => RamadanIftarKpiDefinition::class],
        'bazaar' => ['label_ar' => 'البازارات', 'icon' => 'fa-store', 'accent' => 'bazaar', 'definition' => BazaarKpiDefinition::class],
    ],

    /** Modules whose records are branch-owned events (agenda excluded: its realisation is Monthly). */
    'branch_modules' => ['monthly', 'ramadan', 'bazaar'],

    'stages' => [
        'planned' => ['label_ar' => 'مخطط (مُقدَّم)', 'description_ar' => 'سجل حالي غادر مرحلة المسودة.'],
        'approved' => ['label_ar' => 'معتمد', 'description_ar' => 'وصل إلى الاعتماد النهائي أو ما بعده.'],
        'executed' => ['label_ar' => 'منفذ', 'description_ar' => 'سُجّل تنفيذه أو وصل إلى ما بعد التنفيذ.'],
        'closed' => ['label_ar' => 'مغلق', 'description_ar' => 'أُغلق نهائياً.'],
    ],

    'categories' => [
        'planning' => 'التخطيط',
        'approval' => 'الاعتماد',
        'execution' => 'التنفيذ',
        'attendance' => 'الحضور والفئات',
        'post_execution' => 'ما بعد التنفيذ والمتابعة',
        'evaluation' => 'التقييم',
        'resources' => 'الموارد والاحتياجات',
        'finance' => 'المالية',
        'closure' => 'الإغلاق',
    ],

    'statuses' => [
        'READY' => ['label_ar' => 'جاهز', 'icon' => 'fa-database'],
        'PARTIAL' => ['label_ar' => 'جزئي', 'icon' => 'fa-circle-half-stroke'],
        'UNAVAILABLE' => ['label_ar' => 'غير متاح', 'icon' => 'fa-ban'],
        'NOT_APPLICABLE' => ['label_ar' => 'لا ينطبق', 'icon' => 'fa-minus'],
    ],

    'definitions' => [
        /* ---------------- Phase 3 overview (cross-module) ---------------- */
        'EV-PLANNED' => $kpi(['page' => 'overview', 'name_ar' => 'الفعاليات المخططة', 'name_en' => 'Planned events', 'description_ar' => 'عدد السجلات الحالية التي غادرت مرحلة المسودة ضمن الفترة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['agenda' => 'READY', 'monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'exclusive_with' => $agendaExclusive, 'icon' => 'fa-clipboard-list']),
        'EV-APPROVED' => $kpi(['page' => 'overview', 'name_ar' => 'الفعاليات المعتمدة', 'name_en' => 'Approved events', 'description_ar' => 'عدد الفعاليات التي وصلت إلى الاعتماد النهائي أو ما بعده.', 'category' => 'approval', 'unit' => 'count',
            'calculator' => $sum('approved'), 'availability' => ['agenda' => 'READY', 'monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'exclusive_with' => $agendaExclusive, 'icon' => 'fa-circle-check']),
        'EV-EXECUTED' => $kpi(['page' => 'overview', 'name_ar' => 'الفعاليات المنفذة', 'name_en' => 'Executed events', 'description_ar' => 'عدد الفعاليات التي سُجّل تنفيذها.', 'category' => 'execution', 'unit' => 'count',
            'calculator' => $sum('executed'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $agendaNa, 'icon' => 'fa-person-running']),
        'EV-CLOSED' => $kpi(['page' => 'overview', 'name_ar' => 'الفعاليات المغلقة', 'name_en' => 'Closed events', 'description_ar' => 'عدد الفعاليات المغلقة نهائياً.', 'category' => 'closure', 'unit' => 'count',
            'calculator' => $sum('closed'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $agendaNa, 'icon' => 'fa-lock']),
        'EV-APPROVAL-RATE' => $kpi(['page' => 'overview', 'name_ar' => 'نسبة الاعتماد', 'name_en' => 'Approval rate', 'description_ar' => 'المعتمد ÷ المخطط ضمن الفترة.', 'category' => 'approval', 'comparison_operator' => '>=',
            'calculator' => $ratio('approved', 'planned'), 'availability' => ['agenda' => 'READY', 'monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'exclusive_with' => $agendaExclusive, 'icon' => 'fa-check-double']),
        'EV-EXECUTION-RATE' => $kpi(['page' => 'overview', 'name_ar' => 'نسبة التنفيذ من المعتمد المستحق', 'name_en' => 'Execution rate (due)', 'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ موعده (تاريخه حتى اليوم).', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('executed_due', 'approved_due'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $agendaNa, 'icon' => 'fa-chart-line']),

        /* ---------------- Annual Agenda ---------------- */
        'AG-01' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'فعاليات الأجندة المخططة', 'description_ar' => 'فعاليات الأجندة الحالية التي غادرت المسودة ضمن الفترة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['agenda' => 'READY'], 'icon' => 'fa-calendar-days']),
        'AG-02' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'نسبة فعاليات الأجندة المعتمدة', 'description_ar' => 'المنشورة (معتمدة نهائياً) ÷ المقدّمة.', 'category' => 'approval', 'comparison_operator' => '>=',
            'calculator' => $ratio('approved', 'planned'), 'availability' => ['agenda' => 'READY'], 'icon' => 'fa-check-double']),
        'AG-03' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'نسبة الإعادة للتعديل', 'description_ar' => 'فعاليات أُعيدت للتعديل مرة واحدة على الأقل ÷ المقدّمة.', 'category' => 'approval', 'comparison_operator' => '<=',
            'calculator' => $ratio('wf_returned', 'wf_planned'), 'availability' => ['agenda' => 'READY'], 'icon' => 'fa-rotate-left']),
        'AG-04' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'متوسط زمن الاعتماد', 'description_ar' => 'من أول تقديم (سجل خطوة التقديم في workflow_logs) حتى الاعتماد التنفيذي.', 'category' => 'approval', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('approval_hours', 'approval_measured', $hoursAsDays), 'availability' => ['agenda' => 'PARTIAL'],
            'limitations' => ['يُحتسب فقط للفعاليات التي لها سجل تقديم وتاريخ اعتماد تنفيذي؛ نسخ التعديل المعتمدة لا تمتلك مساراً.'], 'icon' => 'fa-hourglass-half']),
        'AG-05' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'طلبات تعديل/حذف الأجندة', 'description_ar' => 'عدد طلبات التعديل والحذف بتاريخ الطلب ضمن الفترة.', 'category' => 'approval', 'unit' => 'count',
            'calculator' => $sum('change_requests'), 'availability' => ['agenda' => 'READY'],
            'limitations' => ['طلبات الأجندة غير مرتبطة بفرع؛ تظهر لأصحاب صلاحية جميع الفروع فقط.'], 'icon' => 'fa-pen-to-square']),
        'AG-06' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'نسبة استجابة الفروع للمشاركة', 'description_ar' => 'مشاركات الفروع المحددة (مشارك/غير مشارك) ÷ كل سجلات المشاركة.', 'category' => 'planning', 'comparison_operator' => '>=',
            'calculator' => $ratio('part_answered', 'part_rows'), 'availability' => ['agenda' => 'PARTIAL'],
            'limitations' => ['لا يوجد وقت أو جهة تأكيد للمشاركة؛ يُعتمد على حالة المشاركة الحالية.'], 'icon' => 'fa-handshake']),
        'AG-07' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'تحويل المشاركة إلى خطة شهرية', 'description_ar' => 'مشاركات الفروع التي لها نشاط شهري حالي مرتبط ÷ مشاركات الفروع.', 'category' => 'planning', 'comparison_operator' => '>=',
            'calculator' => $ratio('participants_with_plan', 'participants'), 'availability' => ['agenda' => 'PARTIAL'],
            'limitations' => ['المزامنة الإلزامية تُنشئ الأنشطة الشهرية تلقائياً؛ وحذف الأجندة يفصل الأنشطة المرتبطة.'], 'icon' => 'fa-link']),
        'AG-08' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'تنفيذ الأجندة عبر الأنشطة الشهرية', 'description_ar' => 'مشاركات الفروع المستحقة التي نُفّذ نشاطها الشهري المرتبط ÷ المشاركات المستحقة.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('participants_executed', 'participants_due'), 'availability' => ['agenda' => 'PARTIAL'],
            'limitations' => ['وحدة العد: مشاركة (فعالية × فرع). النشاط الشهري يُحتسب ضمن الأنشطة الشهرية ولا يُضاف للأجندة.'],
            'aliases' => ['B-X-08'], 'icon' => 'fa-person-running']),
        'AG-09' => $kpi(['page' => 'module', 'module' => 'agenda', 'name_ar' => 'الالتزام بنافذة التاريخ (±7 أيام)', 'description_ar' => 'مشاركات الخطط غير الموحدة ضمن ±7 أيام من تاريخ الفعالية ÷ مشاركاتها.', 'category' => 'planning', 'comparison_operator' => '=',
            'calculator' => $ratio('part_window_ok', 'part_window_eligible'), 'availability' => ['agenda' => 'READY'], 'icon' => 'fa-calendar-check']),

        /* ---------------- Monthly Activities ---------------- */
        'MA-01' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'الأنشطة الشهرية المخططة', 'description_ar' => 'الأنشطة الحالية التي غادرت المسودة ضمن الفترة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-layer-group']),
        'MA-02' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة التنفيذ من المعتمد المستحق', 'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ تاريخه.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('executed_due', 'approved_due'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-chart-line']),
        'MA-03' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة التنفيذ في الموعد', 'description_ar' => 'المنفذ بتاريخ فعلي لا يتجاوز التاريخ المخطط (أو المعاد جدولته) ÷ المنفذ بتاريخ فعلي.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('ontime', 'ontime_eligible'), 'availability' => ['monthly' => 'PARTIAL'], 'limitations' => ['المقارنة على مستوى اليوم؛ لا يُسجَّل وقت تنفيذ فعلي.'],
            'drilldown' => ['report' => 'on-time', 'module' => 'monthly'], 'icon' => 'fa-clock']),
        'MA-04' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'متوسط التأخير في التنفيذ', 'description_ar' => 'متوسط أيام التأخير للأنشطة المنفذة بعد موعدها.', 'category' => 'execution', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('delay_days', 'late', 1), 'availability' => ['monthly' => 'PARTIAL'], 'drilldown' => ['report' => 'on-time', 'module' => 'monthly'], 'icon' => 'fa-hourglass-end']),
        'MA-05' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة الإلغاء/التأجيل', 'description_ar' => 'المخطط بحالة تنفيذ ملغى أو مؤجل ÷ المخطط.', 'category' => 'execution', 'comparison_operator' => '<=',
            'calculator' => $ratio('cancelled_postponed', 'planned'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-ban']),
        'MA-06' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'متوسط زمن الاعتماد', 'description_ar' => 'من أول تقديم (سجل submitted) حتى اكتمال مسار الاعتماد.', 'category' => 'approval', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('approval_hours', 'approval_measured', $hoursAsDays), 'availability' => ['monthly' => 'PARTIAL'],
            'limitations' => ['الأنشطة المعتمدة عبر مزامنة الأجندة لا تمتلك مسار اعتماد ولا تدخل في المتوسط.'], 'icon' => 'fa-hourglass-half']),
        'MA-07' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'الاعتماد من أول تقديم', 'description_ar' => 'المعتمد دون أي إعادة للتعديل ÷ المعتمد عبر مسار الاعتماد.', 'category' => 'approval', 'comparison_operator' => '>=',
            'calculator' => $ratio('wf_first_pass', 'wf_approved'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-bullseye']),
        'MA-08' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة الإعادة للتعديل', 'description_ar' => 'المقدّم الذي أُعيد للتعديل مرة على الأقل ÷ المقدّم عبر مسار الاعتماد.', 'category' => 'approval', 'comparison_operator' => '<=',
            'calculator' => $ratio('wf_returned', 'wf_planned'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-rotate-left']),
        'MA-09' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'طلبات التعديل والحذف لكل 100 نشاط معتمد', 'description_ar' => 'طلبات التعديل والحذف (بتاريخ الطلب) ÷ الأنشطة المعتمدة × 100.', 'category' => 'approval', 'comparison_operator' => '<=',
            'calculator' => $ratio('change_requests', 'approved'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-pen-to-square']),
        'MA-10' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة استكمال ما بعد التنفيذ', 'description_ar' => 'مطابق لمؤشر MA-02 في دورة العمل الحالية.', 'category' => 'post_execution',
            'calculator' => $ratio('executed_due', 'approved_due'), 'active' => false,
            'limitations' => ['التنفيذ يُسجَّل بإرسال بيانات ما بعد التنفيذ نفسها، لذلك يطابق هذا المؤشر MA-02 ولا يُعرض مكرراً.']]),
        'MA-11' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'زمن إدخال ما بعد التنفيذ', 'description_ar' => 'متوسط الأيام من التاريخ الفعلي حتى أول إرسال لبيانات ما بعد التنفيذ.', 'category' => 'post_execution', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('postexec_lag_days', 'postexec_lag_measured', 1), 'availability' => ['monthly' => 'PARTIAL'], 'limitations' => ['لا توجد مهلة معتمدة؛ يُعرض الزمن دون حكم.'], 'icon' => 'fa-file-circle-check']),
        'MA-12' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة إعادة ما بعد التنفيذ', 'description_ar' => 'المنفذ الذي طُلب توضيح أو رُفض في مراجعة ما بعد التنفيذ ÷ المنفذ.', 'category' => 'post_execution', 'comparison_operator' => '<=',
            'calculator' => $ratio('post_exec_returned', 'executed'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-reply']),
        'MA-13' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة اكتمال التحقق', 'description_ar' => 'الأنشطة المغلقة التي حُسمت كل بنود تحققها ÷ المغلقة (بدون بنود = لم يبدأ).', 'category' => 'post_execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('verif_complete', 'closed'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-list-check']),
        'MA-14' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'دقة بيانات ما بعد التنفيذ', 'description_ar' => 'بنود التحقق الصحيحة ÷ البنود المحسومة.', 'category' => 'post_execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('verif_correct', 'verif_resolved'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-spell-check']),
        'MA-15' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'نسبة اكتمال التقييم', 'description_ar' => 'الأنشطة المغلقة التي قُيّمت ÷ المغلقة.', 'category' => 'evaluation', 'comparison_operator' => '>=',
            'calculator' => $ratio('evaluated', 'closed'), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-clipboard-check']),
        'MA-16' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'متوسط تقييم الأنشطة', 'description_ar' => 'متوسط الدرجة الموزونة (من 10) للأنشطة المقيّمة. قيمة تجميعية لا تكشف تقييماً فردياً.', 'category' => 'evaluation', 'unit' => 'score', 'comparison_operator' => '>=',
            'calculator' => $ratio('evaluation_score_sum', 'evaluation_score_count', 1), 'availability' => ['monthly' => 'READY'], 'icon' => 'fa-star']),
        'MA-17' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'تحقيق الحضور', 'description_ar' => 'الحضور الفعلي ÷ الحضور المتوقع للأنشطة المنفذة التي تملك القيمتين.', 'category' => 'attendance', 'comparison_operator' => '>=',
            'calculator' => $ratio('attendance_actual', 'attendance_planned'), 'availability' => ['monthly' => 'READY'], 'drilldown' => ['report' => 'attendance', 'module' => 'monthly'], 'icon' => 'fa-people-group']),
        'MA-18' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'تغطية الفئات المستهدفة', 'description_ar' => 'الفئات التي وُصل إليها ÷ الفئات المخططة.', 'category' => 'attendance',
            'unavailable_reason' => 'الأنشطة الشهرية لا تسجّل أعداداً فعلية لكل فئة مستهدفة؛ يُعرض توزيع الفئات المخططة فقط.', 'icon' => 'fa-bullseye']),
        'MA-19' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'تأمين احتياجات التنفيذ قبل الفعالية', 'description_ar' => 'الاحتياجات المؤمَّنة ÷ الاحتياجات التي اتُّخذ فيها قرار تأمين.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('needs_secured', 'needs_secured_assessed'), 'availability' => ['monthly' => 'PARTIAL'], 'limitations' => ['القرارات مخزنة في JSON دون وقت أو هوية صاحب القرار.'], 'icon' => 'fa-box-open']),
        'MA-20' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'توفر احتياجات التنفيذ فعلياً', 'description_ar' => 'الاحتياجات «تم توفيرها» ÷ الاحتياجات المقيّمة بعد التنفيذ.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('needs_provided', 'needs_assessed'), 'availability' => ['monthly' => 'PARTIAL'], 'limitations' => ['القيم الفعلية مخزنة في JSON (post_status)؛ لا يُستدل على التوفر من اختيارات التخطيط.'], 'icon' => 'fa-boxes-stacked']),
        'MA-21' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'إنجاز مهام فريق العمل', 'description_ar' => 'المهام المنجزة ÷ مهام الفريق.', 'category' => 'resources',
            'unavailable_reason' => 'لا يُسجَّل إنجاز المهام لأعضاء فرق الأنشطة الشهرية (task_completed غير مستخدم).', 'icon' => 'fa-users-gear']),
        'MA-22' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'تنفيذ فقرات البرنامج', 'description_ar' => 'الفقرات المنفذة ÷ فقرات البرنامج.', 'category' => 'execution',
            'unavailable_reason' => 'حالة الفقرات مخزنة كقيم منطقية داخل JSON ولا يمكن تجميعها في SQL دون تحميل السجلات؛ مؤجل.', 'icon' => 'fa-list-ol']),
        'MA-23' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'عدد الملاحظات التشغيلية أثناء التنفيذ', 'description_ar' => 'عدد المشكلات التشغيلية المسجلة.', 'category' => 'execution', 'unit' => 'count',
            'unavailable_reason' => 'لا يوجد مصدر معتمد لتسجيل المشكلات التشغيلية؛ قرار الالتقاط ما زال معلقاً. لا تُستخدم الملاحظات النصية أو سجلات التدقيق بديلاً.', 'icon' => 'fa-triangle-exclamation']),
        'MA-24' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'زمن الإغلاق بعد التنفيذ', 'description_ar' => 'متوسط الأيام من التاريخ الفعلي حتى سجل الإغلاق (اعتماد ما بعد التنفيذ).', 'category' => 'closure', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('closure_days', 'closure_measured', 1), 'availability' => ['monthly' => 'PARTIAL'], 'limitations' => ['وقت الإغلاق من سجل workflow_action_logs (closed)، لا من updated_at.'], 'icon' => 'fa-lock']),
        'MA-25' => $kpi(['page' => 'module', 'module' => 'monthly', 'name_ar' => 'الالتزام بموعد التخطيط', 'description_ar' => 'التقديم قبل موعد القفل.', 'category' => 'planning',
            'unavailable_reason' => 'اعتماد قاعدة الأيام الخمسة (lock_at) كموعد تخطيط قرار إداري معلّق.', 'icon' => 'fa-calendar-xmark']),

        /* ---------------- Ramadan Iftars ---------------- */
        'RI-01' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'الإفطارات المخططة', 'description_ar' => 'الإفطارات الحالية التي غادرت المسودة ضمن الفترة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-moon']),
        'RI-02' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة الاعتماد', 'description_ar' => 'المعتمد ÷ المقدّم.', 'category' => 'approval', 'comparison_operator' => '>=',
            'calculator' => $ratio('approved', 'planned'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-check-double']),
        'RI-03' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة التنفيذ', 'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ تاريخه.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('executed_due', 'approved_due'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-chart-line']),
        'RI-04' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة المتابعة', 'description_ar' => 'المنفذ الذي قُدّم له تقرير متابعة ÷ المنفذ.', 'category' => 'post_execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('monitored', 'executed'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-clipboard-check']),
        'RI-05' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة الإغلاق', 'description_ar' => 'المغلق ÷ المنفذ.', 'category' => 'closure', 'comparison_operator' => '>=',
            'calculator' => $ratio('closed', 'executed'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-lock']),
        'RI-06' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'تحقيق الوجبات', 'description_ar' => 'الوجبات الفعلية ÷ المخططة للإفطارات المنفذة التي تملك القيمتين.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('meals_actual', 'meals_planned'), 'availability' => ['ramadan' => 'READY'], 'drilldown' => ['report' => 'ramadan-meals'], 'icon' => 'fa-utensils']),
        'RI-07' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'تحقيق الحضور', 'description_ar' => 'الحضور الفعلي ÷ المتوقع للإفطارات المنفذة التي تملك القيمتين.', 'category' => 'attendance', 'comparison_operator' => '>=',
            'calculator' => $ratio('attendance_actual', 'attendance_planned'), 'availability' => ['ramadan' => 'READY'], 'drilldown' => ['report' => 'attendance', 'module' => 'ramadan'], 'icon' => 'fa-people-group']),
        'RI-08' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'تغطية الفئات المستهدفة', 'description_ar' => 'الحضور الفعلي من الفئات المخططة ÷ الأعداد المخططة لها.', 'category' => 'attendance', 'comparison_operator' => '>=',
            'calculator' => $ratio('tg_reached', 'tg_planned'), 'availability' => ['ramadan' => 'READY'], 'limitations' => ['الوصول الفعلي مشتق من سجلات الحضور (attended)، لا من أعداد التخطيط.'], 'icon' => 'fa-bullseye']),
        'RI-09' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة التطابق في المتابعة', 'description_ar' => 'البنود المطابقة ÷ (المطابقة + غير المطابقة).', 'category' => 'post_execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('matched', 'match_assessed'), 'availability' => ['ramadan' => 'PARTIAL'], 'limitations' => ['آخر نسخة من التقرير فقط؛ الاختلافات المصححة لا تُحفظ.'], 'icon' => 'fa-equals']),
        'RI-10' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة عدم التطابق', 'description_ar' => 'البنود غير المطابقة ÷ كل البنود المرصودة.', 'category' => 'post_execution', 'comparison_operator' => '<=',
            'calculator' => $ratio('mismatched', 'match_total'), 'availability' => ['ramadan' => 'PARTIAL'], 'limitations' => ['آخر نسخة من التقرير فقط.'], 'icon' => 'fa-not-equal']),
        'RI-11' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة التصحيح', 'description_ar' => 'الإفطارات التي أُعيد تقرير متابعتها للتصحيح مرة على الأقل ÷ المُتابَعة.', 'category' => 'post_execution', 'comparison_operator' => '<=',
            'calculator' => $ratio('corrected', 'monitored'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-rotate-left']),
        'RI-12' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'زمن الإغلاق', 'description_ar' => 'متوسط الأيام من التاريخ الفعلي حتى closed_at.', 'category' => 'closure', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('closure_days', 'closure_measured', 1), 'availability' => ['ramadan' => 'PARTIAL'], 'limitations' => ['لا توجد مهلة إغلاق معتمدة.'], 'icon' => 'fa-hourglass-end']),
        'RI-13' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'اكتمال احتياجات التنفيذ', 'description_ar' => 'الاحتياجات الإلزامية المكتملة ÷ الاحتياجات الإلزامية للإفطارات المنفذة.', 'category' => 'resources', 'comparison_operator' => '=',
            'calculator' => $ratio('needs_completed', 'needs_required'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-boxes-stacked']),
        'RI-14' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'متوسط زمن الاعتماد', 'description_ar' => 'من أول تقديم (submitted_at) حتى الاعتماد (approved_at).', 'category' => 'approval', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('approval_hours', 'approval_measured', $hoursAsDays), 'availability' => ['ramadan' => 'READY'], 'limitations' => ['يشمل زمن التصحيح بعد الإعادة.'], 'icon' => 'fa-hourglass-half']),
        'RI-15' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة الإعادة للتعديل', 'description_ar' => 'المقدّم الذي أُعيد مرة على الأقل ÷ المقدّم عبر مسار الاعتماد.', 'category' => 'approval', 'comparison_operator' => '<=',
            'calculator' => $ratio('wf_returned', 'wf_planned'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-rotate-left']),
        'RI-16' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'نسبة اعتماد طلبات التغيير', 'description_ar' => 'طلبات التغيير المعتمدة ÷ المحسومة (بتاريخ الطلب).', 'category' => 'approval',
            'calculator' => $ratio('change_approved', 'change_decided'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-code-branch']),
        'RI-17' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'متوسط فرق تاريخ التنفيذ', 'description_ar' => 'متوسط (التاريخ الفعلي − المخطط) بالأيام.', 'category' => 'execution', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('date_variance_days', 'date_variance_measured', 1), 'availability' => ['ramadan' => 'READY'], 'drilldown' => ['report' => 'on-time', 'module' => 'ramadan'], 'icon' => 'fa-calendar-day']),
        'RI-18' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'تحقيق الهدايا/الدروع', 'description_ar' => 'الكميات الفعلية ÷ المخططة للبنود المسجلة فعلياً.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('gifts_actual', 'gifts_planned'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-gift']),
        'RI-19' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'تنفيذ فقرات البرنامج', 'description_ar' => 'الفقرات المكتملة ÷ فقرات البرنامج للإفطارات المنفذة.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('segments_completed', 'segments_total'), 'availability' => ['ramadan' => 'READY'], 'icon' => 'fa-list-ol']),
        'RI-20' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'إنجاز مهام فريق العمل', 'description_ar' => 'الأعضاء الذين أُكدت مهامهم ÷ الأعضاء الذين سُجّلت حالة مهامهم.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('team_tasks_completed', 'team_members'), 'availability' => ['ramadan' => 'READY'], 'limitations' => ['الأعضاء بدون حالة مهمة مسجلة لا يدخلون المقام.'], 'icon' => 'fa-users-gear']),
        'RI-21' => $kpi(['page' => 'module', 'module' => 'ramadan', 'name_ar' => 'الالتزام بالإرشادات المعتمدة', 'description_ar' => 'الإفطارات المقدمة على النسخة الحالية من الإرشادات.', 'category' => 'planning',
            'unavailable_reason' => 'لا يوجد مقام موثوق للمستخدمين المؤهلين؛ مؤجل.', 'icon' => 'fa-book']),

        /* ---------------- Bazaars ---------------- */
        'BZ-01' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'البازارات المخططة', 'description_ar' => 'البازارات التي غادرت المسودة ضمن الفترة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['bazaar' => 'READY'], 'icon' => 'fa-store']),
        'BZ-02' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'نسبة تنفيذ البازارات', 'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ تاريخه.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('executed_due', 'approved_due'), 'availability' => ['bazaar' => 'READY'], 'icon' => 'fa-chart-line']),
        'BZ-03' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'نسبة إشغال الطاولات', 'description_ar' => 'الطاولات المحجوزة ÷ المخططة للبازارات المنفذة.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('tables_booked', 'tables_planned'), 'availability' => ['bazaar' => 'READY'], 'drilldown' => ['report' => 'bazaar-occupancy'], 'icon' => 'fa-table-cells']),
        'BZ-04' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'ثبات المستأجر', 'description_ar' => 'الطاولات المحجوزة بالمستأجر المخطط ÷ المحجوزة.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('renter_matches', 'booked_tables'), 'availability' => ['bazaar' => 'READY'], 'limitations' => ['نموذج التنفيذ يفترض «مطابق» افتراضياً.'], 'icon' => 'fa-user-check']),
        'BZ-05' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'تطابق المعروضات', 'description_ar' => 'الطاولات المطابقة في المعروضات ÷ المقيّمة.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('material_matches', 'material_assessed'), 'availability' => ['bazaar' => 'READY'], 'limitations' => ['نموذج التنفيذ يفترض «مطابق» افتراضياً.'], 'icon' => 'fa-box']),
        'BZ-06' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'نسبة التحصيل', 'description_ar' => 'المبالغ المدفوعة ÷ المستحقة للطاولات المحجوزة.', 'category' => 'finance', 'comparison_operator' => '>=',
            'calculator' => $ratio('amount_paid', 'amount_due'), 'availability' => ['bazaar' => 'PARTIAL'], 'limitations' => ['حالة الدفع مشتقة من المبالغ لأن payment_status يُختار يدوياً وقد يُستبدل بالخصم.'], 'icon' => 'fa-money-bill-wave']),
        'BZ-07' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'طاولات غير مسددة أو مسددة جزئياً', 'description_ar' => 'الطاولات المحجوزة التي دُفع منها أقل من المستحق.', 'category' => 'finance', 'unit' => 'count',
            'calculator' => $sum('tables_unpaid_partial'), 'availability' => ['bazaar' => 'PARTIAL'], 'icon' => 'fa-file-invoice-dollar']),
        'BZ-08' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'طلبات الخصم', 'description_ar' => 'عدد طلبات الخصم على طاولات البازارات المقدّمة.', 'category' => 'finance', 'unit' => 'count',
            'calculator' => $sum('discount_requests'), 'availability' => ['bazaar' => 'READY'], 'icon' => 'fa-percent']),
        'BZ-09' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'نسبة اعتماد الخصم', 'description_ar' => 'الخصومات المعتمدة ÷ المحسومة.', 'category' => 'finance',
            'calculator' => $ratio('discount_approved', 'discount_decided'), 'availability' => ['bazaar' => 'READY'], 'icon' => 'fa-check']),
        'BZ-10' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'قيمة الخصومات الممنوحة', 'description_ar' => 'مجموع (المبلغ الأصلي − النهائي) للخصومات المعتمدة.', 'category' => 'finance', 'unit' => 'currency',
            'calculator' => $sum('discount_value'), 'availability' => ['bazaar' => 'READY'], 'icon' => 'fa-coins']),
        'BZ-11' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'الإيراد المحصل مقابل المخطط', 'description_ar' => 'المدفوع ÷ الإيجار المخطط للطاولات المحجوزة.', 'category' => 'finance', 'comparison_operator' => '>=',
            'calculator' => $ratio('amount_paid', 'planned_rent'), 'availability' => ['bazaar' => 'PARTIAL'], 'icon' => 'fa-sack-dollar']),
        'BZ-12' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'زمن الاعتماد', 'description_ar' => 'من submitted_at حتى approved_at.', 'category' => 'approval', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('approval_hours', 'approval_measured', $hoursAsDays), 'availability' => ['bazaar' => 'PARTIAL'], 'limitations' => ['submitted_at يُستبدل عند إعادة التقديم.'], 'icon' => 'fa-hourglass-half']),
        'BZ-13' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'زمن التحقق بعد التنفيذ', 'description_ar' => 'من post_execution_submitted_at حتى verified_at.', 'category' => 'post_execution', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('verification_hours', 'verification_measured', $hoursAsDays), 'availability' => ['bazaar' => 'PARTIAL'], 'limitations' => ['وقت الإرسال يُعاد ضبطه عند الحفظ غير المكتمل.'], 'icon' => 'fa-magnifying-glass']),
        'BZ-14' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'زمن إغلاق البازار', 'description_ar' => 'متوسط الأيام من تاريخ البازار حتى closed_at.', 'category' => 'closure', 'unit' => 'days', 'comparison_operator' => '<=',
            'calculator' => $ratio('closure_days', 'closure_measured', 1), 'availability' => ['bazaar' => 'PARTIAL'], 'limitations' => ['closed_at يُسجَّل منذ المرحلة الرابعة؛ البازارات المغلقة سابقاً مستبعدة.'], 'icon' => 'fa-lock']),
        'BZ-15' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'اكتمال احتياجات التنفيذ', 'description_ar' => 'الاحتياجات المكتملة ÷ المطلوبة.', 'category' => 'resources',
            'unavailable_reason' => 'مسار البازار لا يسجّل اكتمال احتياجات التنفيذ؛ لا يُستدل عليه من اختيارات التخطيط.', 'icon' => 'fa-boxes-stacked']),
        'BZ-16' => $kpi(['page' => 'module', 'module' => 'bazaar', 'name_ar' => 'نسبة الإعادة', 'description_ar' => 'البازارات المعادة للتعديل ÷ المقدّمة.', 'category' => 'approval',
            'unavailable_reason' => 'لا يُحفظ سجل حالات البازار؛ لا يمكن معرفة الإعادات السابقة.', 'icon' => 'fa-rotate-left']),

        /* ---------------- Branch (branch-owned events: Monthly, Ramadan, Bazaar) ---------------- */
        'BR-01' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'الفعاليات المخططة', 'description_ar' => 'فعاليات الفرع (أنشطة شهرية، إفطارات، بازارات) التي غادرت المسودة.', 'category' => 'planning', 'unit' => 'count',
            'calculator' => $sum('planned'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'aliases' => ['B-X-01'], 'icon' => 'fa-clipboard-list']),
        'BR-02' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'الفعاليات المعتمدة', 'description_ar' => 'فعاليات الفرع المعتمدة نهائياً أو ما بعده.', 'category' => 'approval', 'unit' => 'count',
            'calculator' => $sum('approved'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'icon' => 'fa-circle-check']),
        'BR-03' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'الفعاليات المنفذة', 'description_ar' => 'فعاليات الفرع المنفذة.', 'category' => 'execution', 'unit' => 'count',
            'calculator' => $sum('executed'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'icon' => 'fa-person-running']),
        'BR-04' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'نسبة التنفيذ', 'description_ar' => 'المنفذ ÷ المعتمد الذي حلّ تاريخه.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('executed_due', 'approved_due'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'aliases' => ['B-X-02'], 'icon' => 'fa-chart-line']),
        'BR-05' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'نسبة الفعاليات المنفذة في موعدها', 'description_ar' => 'المنفذ بتاريخ فعلي لا يتجاوز المخطط ÷ المنفذ بتاريخ فعلي.', 'category' => 'execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('ontime', 'ontime_eligible'), 'availability' => ['monthly' => 'PARTIAL', 'ramadan' => 'READY'], 'not_applicable' => $branchModulesNa, 'unavailable' => $bazaarNoActualDate,
            'limitations' => ['مصدر التاريخ الفعلي: actual_date للأنشطة الشهرية والإفطارات؛ لا يُستخدم updated_at.'], 'drilldown' => ['report' => 'on-time', 'module' => 'monthly'], 'icon' => 'fa-clock']),
        'BR-06' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'نسبة اكتمال مراجعة ما بعد التنفيذ', 'description_ar' => 'المنفذ الذي اكتملت مراجعته (شهري: مغلق؛ رمضان: تقرير متابعة معتمد؛ بازار: متحقق) ÷ المنفذ.', 'category' => 'post_execution', 'comparison_operator' => '>=',
            'calculator' => $ratio('reviewed', 'executed'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'icon' => 'fa-file-circle-check']),
        'BR-07' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'نسبة الإغلاق', 'description_ar' => 'المغلق ÷ المنفذ.', 'category' => 'closure', 'comparison_operator' => '>=',
            'calculator' => $ratio('closed', 'executed'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'icon' => 'fa-lock']),
        'BR-08' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'تحقيق الحضور', 'description_ar' => 'الحضور الفعلي ÷ المتوقع حيث تتوفر القيمتان.', 'category' => 'attendance', 'comparison_operator' => '>=',
            'calculator' => $ratio('attendance_actual', 'attendance_planned'), 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY'], 'not_applicable' => $branchModulesNa, 'unavailable' => $bazaarNoAttendance,
            'aliases' => ['B-X-04'], 'drilldown' => ['report' => 'attendance', 'module' => 'monthly'], 'icon' => 'fa-people-group']),
        'BR-09' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'عدد الفعاليات المدخلة / المخططة شهرياً', 'description_ar' => 'متوسط الفعاليات المخططة لكل شهر منقضٍ من الفترة. '.$targetNote.' (مقترح: 4 فأكثر)', 'category' => 'planning', 'unit' => 'count_decimal', 'comparison_operator' => '>=',
            'calculator' => ['type' => 'per_month', 'numerator' => 'planned'], 'availability' => ['monthly' => 'READY', 'ramadan' => 'READY', 'bazaar' => 'READY'], 'not_applicable' => $branchModulesNa, 'icon' => 'fa-calendar-plus']),
        'BR-11' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'الالتزام بخطة الأجندة', 'description_ar' => 'مشاركات الفرع المستحقة في الأجندة التي نُفّذ نشاطها الشهري المرتبط ÷ المشاركات المستحقة.', 'category' => 'planning', 'comparison_operator' => '>=',
            'calculator' => $ratio('participants_executed', 'participants_due'), 'availability' => ['agenda' => 'PARTIAL'],
            'limitations' => ['وحدة العد: مشاركة فرع في فعالية أجندة منشورة حلّ تاريخها. النشاط الشهري المنفذ يُحتسب مرة واحدة فقط.'], 'aliases' => ['B-X-08'], 'icon' => 'fa-calendar-check']),
        'BR-12' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'اكتمال احتياجات التنفيذ', 'description_ar' => 'الاحتياجات المتوفرة فعلياً ÷ المقيّمة، حيث تتوفر بيانات فعلية.', 'category' => 'resources', 'comparison_operator' => '>=',
            'calculator' => $ratio('needs_completed', 'needs_required'),
            'availability' => ['ramadan' => 'READY', 'monthly' => ['status' => 'PARTIAL', 'numerator' => 'needs_provided', 'denominator' => 'needs_assessed']],
            'not_applicable' => $branchModulesNa, 'unavailable' => ['bazaar' => 'مسار البازار لا يسجّل اكتمال الاحتياجات.'],
            'limitations' => ['رمضان: الاحتياجات الإلزامية المكتملة؛ الشهري: post_status = provided من JSON.'], 'aliases' => ['B-X-05'], 'icon' => 'fa-boxes-stacked']),
        'BR-13' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'تغطية الفئات المستهدفة', 'description_ar' => 'الحضور الفعلي من الفئات المخططة ÷ الأعداد المخططة.', 'category' => 'attendance', 'comparison_operator' => '>=',
            'calculator' => $ratio('tg_reached', 'tg_planned'), 'availability' => ['ramadan' => 'READY'], 'not_applicable' => $branchModulesNa,
            'unavailable' => ['monthly' => 'لا تُسجَّل أعداد فعلية للفئات في الأنشطة الشهرية.', 'bazaar' => 'لا تُسجَّل فئات فعلية للبازار.'], 'icon' => 'fa-bullseye']),
        'BR-14' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'أداء فرق العمل', 'description_ar' => 'مؤشر موحد لأداء فرق العمل.', 'category' => 'resources',
            'unavailable_reason' => 'إنجاز المهام لا يُسجَّل إلا للإفطارات (انظر RI-20)؛ لا يُبنى مؤشر موحد للفرع.', 'icon' => 'fa-users-gear']),
        'BR-15' => $kpi(['page' => 'branch', 'family' => 'branch', 'name_ar' => 'عدد الملاحظات التشغيلية أثناء التنفيذ', 'description_ar' => 'المشكلات التشغيلية المسجلة.', 'category' => 'execution', 'unit' => 'count',
            'unavailable_reason' => 'لا يوجد مصدر معتمد للملاحظات التشغيلية؛ قرار الالتقاط معلّق.', 'icon' => 'fa-triangle-exclamation']),
    ],
];
