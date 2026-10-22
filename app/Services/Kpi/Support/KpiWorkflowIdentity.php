<?php

namespace App\Services\Kpi\Support;

use App\Modules\Events\Models\AgendaEvent;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Support\EventAggregateIdentity;

/**
 * Read-only compatibility for record-type spellings stored in workflow_instances /
 * workflow_logs / workflow_action_logs. Historical rows are never rewritten and no morph map
 * is registered: KPI queries simply accept every known spelling of an aggregate.
 */
class KpiWorkflowIdentity
{
    /** workflow_action_logs.module per KPI module (Agenda writes no action logs for submission). */
    private const ACTION_LOG_MODULES = [
        'monthly' => 'monthly_activities',
        'ramadan' => 'ramadan_iftars',
    ];

    /** Comments written by DynamicWorkflowService auto-approval (both locales); excluded from human decisions. */
    private const AUTO_APPROVAL_COMMENTS = [
        'تم الاعتماد تلقائيا حسب تفضيل المستخدم.',
        'Approved automatically by user preference.',
    ];

    /** @return array<int, string> */
    public static function typesFor(string $module): array
    {
        switch ($module) {
            case 'agenda':
                return EventAggregateIdentity::acceptedTypes(AgendaEvent::class);
            case 'monthly':
                return EventAggregateIdentity::acceptedTypes(MonthlyActivity::class);
            case 'ramadan':
                // Ramadan was introduced after the namespace cutover: one spelling only.
                return [RamadanIftar::class];
        }

        return [];
    }

    public static function sqlList(string $module): string
    {
        return KpiSql::literalList(self::typesFor($module));
    }

    public static function actionLogModule(string $module): ?string
    {
        return self::ACTION_LOG_MODULES[$module] ?? null;
    }

    /** SQL condition excluding auto-approval rows from a workflow_logs alias. */
    public static function humanDecisionSql(string $alias): string
    {
        $comments = KpiSql::literalList(array_merge(
            self::AUTO_APPROVAL_COMMENTS,
            array_values(array_filter([__('app.workflow_auto_approval.log_comment')], 'is_string'))
        ));

        return '('.$alias.'.comment IS NULL OR '.$alias.'.comment NOT IN ('.$comments.'))';
    }
}
