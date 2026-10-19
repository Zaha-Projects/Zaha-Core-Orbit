<?php

namespace App\Services\Kpi\Modules;

use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Services\Kpi\Support\KpiMetricSource;
use App\Services\Kpi\Support\KpiSql;
use App\Services\Kpi\Support\KpiWorkflowIdentity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RamadanIftarKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'ramadan';
    }

    public function table(): string
    {
        return 'ramadan_iftars';
    }

    protected function dateColumn(): string
    {
        return 'planned_date';
    }

    /**
     * Live = not soft-deleted and not superseded. An approved change request creates a new
     * version (parent_version_id) while the source keeps its approved status.
     */
    public function liveSql(string $alias): string
    {
        $newer = $alias.'_newer';

        return $alias.'.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM ramadan_iftars '.$newer
            .' WHERE '.$newer.'.deleted_at IS NULL AND '.$newer.'.parent_version_id = '.$alias.'.id)';
    }

    protected function rawStageConditions(string $alias): array
    {
        return [
            'planned' => $alias.'.status <> '.KpiSql::literal(RamadanIftar::STATUS_DRAFT),
            'approved' => $alias.'.status = '.KpiSql::literal(RamadanIftar::STATUS_APPROVED),
            'executed' => $alias.'.execution_status = '.KpiSql::literal(RamadanIftar::EXECUTION_STATUS_COMPLETED),
            // Closure is a timestamp, not a status value.
            'closed' => $alias.'.closed_at IS NOT NULL',
        ];
    }

    public function notes(): array
    {
        return ['نتائج المطابقة في المتابعة تمثل آخر نسخة من تقرير المتابعة؛ الاختلافات قبل التصحيح لا تُحفظ.'];
    }

    protected function mainMetrics(string $alias): array
    {
        $a = $alias;
        $subject = KpiSql::literal(EventSubjectTypes::RAMADAN_IFTAR);
        $planned = $this->stageSql('planned', $a);
        $approved = $this->stageSql('approved', $a);
        $executed = $this->stageSql('executed', $a);
        $closed = $this->stageSql('closed', $a);
        $types = KpiWorkflowIdentity::sqlList('ramadan');

        $eligible = '('.$executed.') AND '.$a.'.actual_date IS NOT NULL';
        $late = $eligible.' AND '.$a.'.actual_date > '.$a.'.planned_date';
        $attendance = '('.$executed.') AND '.$a.'.actual_attendance IS NOT NULL AND '.$a.'.expected_attendance > 0';
        $meals = '('.$executed.') AND '.$a.'.actual_meals_count IS NOT NULL AND '.$a.'.planned_meals_count > 0';

        $report = fn (array $statuses): string => 'EXISTS (SELECT 1 FROM monitoring_reports mr WHERE mr.subject_type = '.$subject
            .' AND mr.subject_id = '.$a.'.id AND mr.status IN ('.KpiSql::literalList($statuses).'))';
        $verification = fn (array $matchStatuses): string => '(SELECT COUNT(*) FROM post_execution_verifications v JOIN monitoring_reports mr ON mr.id = v.monitoring_report_id'
            .' WHERE mr.subject_type = '.$subject.' AND mr.subject_id = '.$a.'.id AND v.match_status IN ('.KpiSql::literalList($matchStatuses).'))';
        $needs = fn (string $condition): string => '(SELECT COUNT(*) FROM subject_execution_needs n WHERE n.subject_type = '.$subject
            .' AND n.subject_id = '.$a.'.id AND n.is_required = TRUE'.$condition.')';
        $team = fn (string $condition): string => '(SELECT COUNT(*) FROM execution_team_members tm JOIN execution_teams et ON et.id = tm.execution_team_id'
            .' WHERE et.subject_type = '.$subject.' AND et.subject_id = '.$a.'.id AND tm.task_completed IS NOT NULL'.$condition.')';
        $plannedGroups = 'SELECT tg2.target_group_id FROM event_target_group tg2 WHERE tg2.subject_type = '.$subject.' AND tg2.subject_id = '.$a.'.id AND tg2.planned_count > 0';
        $approvalHours = KpiSql::hoursBetween($a.'.submitted_at', $a.'.approved_at');
        $closureLag = KpiSql::daysBetween($a.'.actual_date', KpiSql::dateOf($a.'.closed_at'));

        return [
            'ontime_eligible' => KpiSql::countIf($eligible),
            'ontime' => KpiSql::countIf($eligible.' AND '.$a.'.actual_date <= '.$a.'.planned_date'),
            'late' => KpiSql::countIf($late),
            'delay_days' => KpiSql::sumIf($late, KpiSql::daysBetween($a.'.planned_date', $a.'.actual_date')),
            'executed_no_actual_date' => KpiSql::countIf('('.$executed.') AND '.$a.'.actual_date IS NULL'),
            'monitored' => KpiSql::countIf('('.$executed.') AND '.$report([MonitoringReport::STATUS_SUBMITTED, MonitoringReport::STATUS_APPROVED])),
            // Post-execution review is complete when the supervisor approves the monitoring report.
            'reviewed' => KpiSql::countIf('('.$executed.') AND '.$report([MonitoringReport::STATUS_APPROVED])),
            'corrected' => KpiSql::countIf('('.$executed.') AND EXISTS (SELECT 1 FROM workflow_action_logs l WHERE l.module = '.KpiSql::literal(RamadanIftar::WORKFLOW_MODULE)
                .' AND l.entity_type IN ('.$types.') AND l.entity_id = '.$a.".id AND l.action_type = 'monitoring_returned')"),
            'attendance_events' => KpiSql::countIf($attendance),
            'attendance_planned' => KpiSql::sumIf($attendance, $a.'.expected_attendance'),
            'attendance_actual' => KpiSql::sumIf($attendance, $a.'.actual_attendance'),
            'meals_events' => KpiSql::countIf($meals),
            'meals_planned' => KpiSql::sumIf($meals, $a.'.planned_meals_count'),
            'meals_actual' => KpiSql::sumIf($meals, $a.'.actual_meals_count'),
            'matched' => KpiSql::sumIf($executed, $verification(['matched'])),
            'mismatched' => KpiSql::sumIf($executed, $verification(['mismatched'])),
            'not_observed' => KpiSql::sumIf($executed, $verification(['not_observed'])),
            'match_assessed' => KpiSql::sumIf($executed, $verification(['matched', 'mismatched'])),
            'match_total' => KpiSql::sumIf($executed, $verification(['matched', 'mismatched', 'not_observed'])),
            'closure_days' => KpiSql::sumIf('('.$closed.') AND '.$a.'.actual_date IS NOT NULL', $closureLag),
            'closure_measured' => KpiSql::countValue('('.$closed.') AND '.$a.'.actual_date IS NOT NULL', $closureLag),
            'needs_required' => KpiSql::sumIf($executed, $needs('')),
            'needs_completed' => KpiSql::sumIf($executed, $needs(' AND n.status = '.KpiSql::literal(SubjectExecutionNeed::STATUS_COMPLETED))),
            'approval_hours' => KpiSql::sumIf($approved, $approvalHours),
            'approval_measured' => KpiSql::countValue($approved, $approvalHours),
            'wf_planned' => KpiSql::countIf('('.$planned.') AND '.$this->workflowInstanceExists($a, $types)),
            'wf_returned' => KpiSql::countIf('('.$planned.') AND '.$this->workflowInstanceExists($a, $types, ' AND wi.edit_request_count > 0')),
            'date_variance_days' => KpiSql::sumIf($eligible, KpiSql::daysBetween($a.'.planned_date', $a.'.actual_date')),
            'date_variance_measured' => KpiSql::countIf($eligible),
            'gifts_planned' => KpiSql::sumIf($executed, '(SELECT COALESCE(SUM(g.planned_quantity), 0) FROM ramadan_iftar_gifts g WHERE g.ramadan_iftar_id = '.$a.'.id AND g.actual_quantity IS NOT NULL)'),
            'gifts_actual' => KpiSql::sumIf($executed, '(SELECT COALESCE(SUM(g.actual_quantity), 0) FROM ramadan_iftar_gifts g WHERE g.ramadan_iftar_id = '.$a.'.id AND g.actual_quantity IS NOT NULL)'),
            'segments_total' => KpiSql::sumIf($executed, '(SELECT COUNT(*) FROM ramadan_iftar_program_segments ps WHERE ps.ramadan_iftar_id = '.$a.'.id)'),
            'segments_completed' => KpiSql::sumIf($executed, '(SELECT COUNT(*) FROM ramadan_iftar_program_segments ps WHERE ps.ramadan_iftar_id = '.$a.".id AND ps.execution_status = 'completed')"),
            'team_members' => KpiSql::sumIf($executed, $team('')),
            'team_tasks_completed' => KpiSql::sumIf($executed, $team(' AND tm.task_completed = TRUE')),
            'tg_planned' => KpiSql::sumIf($executed, '(SELECT COALESCE(SUM(tg.planned_count), 0) FROM event_target_group tg WHERE tg.subject_type = '.$subject.' AND tg.subject_id = '.$a.'.id AND tg.planned_count > 0)'),
            'tg_reached' => KpiSql::sumIf($executed, '(SELECT COUNT(*) FROM ramadan_iftar_attendees att WHERE att.ramadan_iftar_id = '.$a.'.id AND att.attended = TRUE AND att.target_group_id IN ('.$plannedGroups.'))'),
        ];
    }

    /**
     * User-attributable Ramadan actions. Actors: created_by, workflow_action_logs.performed_by
     * (submitted, execution_completed, monitoring_*, iftar_closed), workflow_logs.acted_by,
     * post_execution_verifications.verified_by; returned monitoring reports are attributed to
     * monitoring_reports.monitor_user_id (the officer whose report was returned).
     */
    protected function actorSources(): array
    {
        $types = KpiWorkflowIdentity::sqlList('ramadan');
        $subject = KpiSql::literal(EventSubjectTypes::RAMADAN_IFTAR);
        $planned = $this->stageSql('planned', 's');
        $approved = $this->stageSql('approved', 's');
        $returned = '('.$planned.') AND '.$this->workflowInstanceExists('s', $types, ' AND wi.edit_request_count > 0');
        $createToSubmit = KpiSql::hoursBetween('s.created_at', 's.submitted_at');
        $firstExecution = 'l.performed_at = '.$this->firstActionLogSql('l.entity_id', 'execution_completed').' AND s.actual_date IS NOT NULL';
        $executionLag = KpiSql::daysBetween('s.actual_date', KpiSql::dateOf('l.performed_at'));
        $monitoringTurnaround = KpiSql::daysBetween(KpiSql::dateOf($this->firstActionLogSql('s.id', 'execution_completed')), KpiSql::dateOf('l.performed_at'));
        $statusLabel = 'CASE WHEN '.$returned." THEN 'مقدّم — أُعيد للتعديل' WHEN ".$planned." THEN 'مقدّم' ELSE 'مسودة' END";

        return [
            $this->actorSource('u_created', fn (): Builder => $this->liveQuery('s'), 's.created_at', 's.created_by', [
                'u_created' => 'COUNT(*)',
                'u_created_submitted' => KpiSql::countIf($planned),
                'u_created_returned' => KpiSql::countIf($returned),
                'u_created_approved_wf' => KpiSql::countIf('('.$approved.') AND '.$this->workflowInstanceExists('s', $types)),
                'u_created_first_pass' => KpiSql::countIf('('.$approved.') AND '.$this->workflowInstanceExists('s', $types, ' AND wi.edit_request_count = 0')),
                'u_create_to_submit_hours' => KpiSql::sumIf('TRUE', $createToSubmit),
                'u_create_to_submit_measured' => KpiSql::countValue('TRUE', $createToSubmit),
            ], ['action' => "'إنشاء خطة إفطار'", 'result' => $statusLabel, 'duration_hours' => $createToSubmit], ['returned' => $returned]),
            $this->actionLogSource('u_submissions', ['submitted'], [
                'u_submitted' => 'COUNT(DISTINCT l.entity_id)',
                'u_resubmissions' => '(COUNT(*) - COUNT(DISTINCT l.entity_id))',
            ]),
            $this->actionLogSource('u_post_execution', ['execution_completed'], [
                'u_postexec' => 'COUNT(DISTINCT l.entity_id)',
                'u_postexec_lag_days' => KpiSql::sumIf($firstExecution, $executionLag),
                'u_postexec_lag_measured' => KpiSql::countValue($firstExecution, $executionLag),
            ], ['duration_days' => $executionLag]),
            $this->decisionSource('s.submitted_at', true),
            $this->actionLogSource('u_monitoring_reviews', ['monitoring_approved', 'monitoring_returned'], [
                'u_pe_reviews' => 'COUNT(*)',
                'u_pe_returned' => KpiSql::countIf("l.action_type = 'monitoring_returned'"),
            ], ['result' => self::labelCase('l.action_type', ['monitoring_approved' => 'اعتماد', 'monitoring_returned' => 'إعادة للتصحيح'])]),
            $this->actionLogSource('u_closures', ['iftar_closed'], ['u_closures' => 'COUNT(*)'], ['result' => "'إغلاق'"]),
            $this->actionLogSource('u_monitoring', ['monitoring_verification_submitted'], [
                'u_cases_handled' => 'COUNT(DISTINCT l.entity_id)',
                'u_cases_completed' => 'COUNT(DISTINCT l.entity_id)',
                'u_turnaround_days' => KpiSql::sumIf('TRUE', $monitoringTurnaround),
                'u_turnaround_measured' => KpiSql::countValue('TRUE', $monitoringTurnaround),
            ], ['duration_days' => $monitoringTurnaround]),
            new KpiMetricSource(
                'u_reports_returned',
                fn (): Builder => DB::table('workflow_action_logs as l')
                    ->join('ramadan_iftars as s', 's.id', '=', 'l.entity_id')
                    ->join('monitoring_reports as mr', function ($join): void {
                        $join->on('mr.subject_id', '=', 'l.entity_id')->where('mr.subject_type', '=', EventSubjectTypes::RAMADAN_IFTAR);
                    })
                    ->where('l.module', RamadanIftar::WORKFLOW_MODULE)
                    ->whereRaw('l.entity_type IN ('.$types.')')
                    ->where('l.action_type', 'monitoring_returned'),
                'l.performed_at',
                fn (?array $branchIds) => $this->branchScopeSql('s', $branchIds),
                fn (Builder $query): string => 's.branch_id',
                ['u_reports_returned' => 'COUNT(*)'],
                'mr.monitor_user_id',
                ['event' => 's.title', 'action_date' => 'l.performed_at', 'action' => "'إعادة تقرير المتابعة للتصحيح'", 'result' => "'أُعيد'"]
            ),
            $this->actorSource('u_matches', fn (): Builder => DB::table('post_execution_verifications as v')
                ->join('monitoring_reports as mr', 'mr.id', '=', 'v.monitoring_report_id')
                ->join('ramadan_iftars as s', 's.id', '=', 'mr.subject_id')
                ->where('mr.subject_type', EventSubjectTypes::RAMADAN_IFTAR),
                'v.verified_at', 'v.verified_by', [
                    'u_matched' => KpiSql::countIf("v.match_status = 'matched'"),
                    'u_mismatched' => KpiSql::countIf("v.match_status = 'mismatched'"),
                ], ['action' => 'COALESCE(v.field_label, v.field_key)', 'result' => self::labelCase('v.match_status', ['matched' => 'مطابق', 'mismatched' => 'غير مطابق', 'not_observed' => 'لم يُرصد', 'not_applicable' => 'لا ينطبق'])]),
        ];
    }

    /** Change requests by request date and branch. */
    protected function secondarySources(): array
    {
        return [new KpiMetricSource(
            'change_requests',
            fn (): Builder => DB::table('ramadan_iftar_change_requests as r'),
            'r.created_at',
            fn (?array $branchIds) => $branchIds === null ? null : ($branchIds === [] ? '1 = 0' : 'r.branch_id IN ('.KpiSql::intList($branchIds).')'),
            fn (Builder $query): string => 'r.branch_id',
            [
                'change_requests' => 'COUNT(*)',
                'change_approved' => KpiSql::countIf("r.status = 'approved'"),
                'change_decided' => KpiSql::countIf('r.status IN ('.KpiSql::literalList(['approved', 'rejected']).')'),
            ]
        )];
    }
}
