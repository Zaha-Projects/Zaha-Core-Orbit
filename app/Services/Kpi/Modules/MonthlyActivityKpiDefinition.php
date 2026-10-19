<?php

namespace App\Services\Kpi\Modules;

use App\Services\Kpi\Support\KpiMetricSource;
use App\Services\Kpi\Support\KpiSql;
use App\Services\Kpi\Support\KpiWorkflowIdentity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MonthlyActivityKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'monthly';
    }

    public function table(): string
    {
        return 'monthly_activities';
    }

    protected function dateColumn(): string
    {
        return 'proposed_date';
    }

    /**
     * Live = not soft-deleted, not archived, and the newest version. An approved edit request
     * archives its source; a post-approval edit starts a newer draft version that points back
     * through previous_version_id / parent_version_id.
     */
    public function liveSql(string $alias): string
    {
        $newer = $alias.'_newer';

        return $alias.'.deleted_at IS NULL AND '.$alias.'.is_archived = FALSE AND '.$alias.".status <> 'archived'"
            .' AND NOT EXISTS (SELECT 1 FROM monthly_activities '.$newer.' WHERE '.$newer.'.deleted_at IS NULL'
            .' AND ('.$newer.'.previous_version_id = '.$alias.'.id OR '.$newer.'.parent_version_id = '.$alias.'.id))';
    }

    protected function rawStageConditions(string $alias): array
    {
        $a = $alias;

        return [
            'planned' => $a.".status <> 'draft'",
            'approved' => $a.'.status IN ('.KpiSql::literalList(['approved', 'post_execution_submitted', 'closed', 'evaluated']).')'
                .' OR '.$a.'.lifecycle_status IN ('.KpiSql::literalList(['Approved', 'Scheduled', 'Executed', 'Evaluated', 'Closed']).')',
            'executed' => $a.".execution_status = 'executed'"
                .' OR '.$a.'.status IN ('.KpiSql::literalList(['post_execution_submitted', 'closed', 'evaluated']).')',
            // Supervisor approval of post-execution closes the activity; evaluation follows closure.
            'closed' => $a.'.status IN ('.KpiSql::literalList(['closed', 'evaluated']).')',
        ];
    }

    public function notes(): array
    {
        return ['التنفيذ يُسجَّل بتاريخ فعلي (يوم) دون وقت؛ مقارنة الموعد تتم على مستوى اليوم.'];
    }

    protected function mainMetrics(string $alias): array
    {
        $a = $alias;
        $planned = $this->stageSql('planned', $a);
        $approved = $this->stageSql('approved', $a);
        $executed = $this->stageSql('executed', $a);
        $closed = $this->stageSql('closed', $a);
        $types = KpiWorkflowIdentity::sqlList('monthly');

        $plannedDate = 'COALESCE('.$a.'.rescheduled_date, '.$a.'.proposed_date)';
        $eligible = '('.$executed.') AND '.$a.'.actual_date IS NOT NULL';
        $late = $eligible.' AND '.$a.'.actual_date > '.$plannedDate;
        $expected = 'COALESCE('.$a.'.expected_attendance_to, '.$a.'.expected_attendance)';
        $attendance = '('.$executed.') AND '.$a.'.actual_attendance IS NOT NULL AND '.$expected.' > 0';

        $actionLog = fn (string $action): string => '(SELECT MIN(l.performed_at) FROM workflow_action_logs l WHERE l.module = '
            ."'monthly_activities' AND l.entity_type IN (".$types.') AND l.entity_id = '.$a.'.id AND l.action_type = '.KpiSql::literal($action).')';
        $finalApproval = '(SELECT MAX(wi2.completed_at) FROM workflow_instances wi2 WHERE wi2.entity_id = '.$a.'.id'
            .' AND wi2.entity_type IN ('.$types.") AND wi2.status = 'approved')";
        $approvalHours = KpiSql::hoursBetween($actionLog('submitted'), $finalApproval);
        $postExecutionLag = KpiSql::daysBetween($a.'.actual_date', KpiSql::dateOf($actionLog('post_execution_submitted')));
        $closureLag = KpiSql::daysBetween($a.'.actual_date', KpiSql::dateOf($actionLog('closed')));

        $verifications = fn (string $condition): string => '(SELECT COUNT(*) FROM post_execution_verifications v WHERE v.monthly_activity_id = '.$a.'.id'.$condition.')';
        $evaluationScore = '(SELECT ev.normalized_score FROM activity_evaluations ev WHERE ev.monthly_activity_id = '.$a.'.id)';

        $metrics = [
            'ontime_eligible' => KpiSql::countIf($eligible),
            'ontime' => KpiSql::countIf($eligible.' AND '.$a.'.actual_date <= '.$plannedDate),
            'late' => KpiSql::countIf($late),
            'delay_days' => KpiSql::sumIf($late, KpiSql::daysBetween($plannedDate, $a.'.actual_date')),
            'executed_no_actual_date' => KpiSql::countIf('('.$executed.') AND '.$a.'.actual_date IS NULL'),
            'cancelled_postponed' => KpiSql::countIf('('.$planned.') AND '.$a.'.execution_status IN ('.KpiSql::literalList(['cancelled', 'postponed']).')'),
            // Supervisor approval of the post-execution data is the activity's closure.
            'reviewed' => KpiSql::countIf($closed),
            'attendance_events' => KpiSql::countIf($attendance),
            'attendance_planned' => KpiSql::sumIf($attendance, $expected),
            'attendance_actual' => KpiSql::sumIf($attendance, $a.'.actual_attendance'),
            'wf_planned' => KpiSql::countIf('('.$planned.') AND '.$this->workflowInstanceExists($a, $types)),
            'wf_returned' => KpiSql::countIf('('.$planned.') AND '.$this->workflowInstanceExists($a, $types, ' AND wi.edit_request_count > 0')),
            'wf_approved' => KpiSql::countIf('('.$approved.') AND '.$this->workflowInstanceExists($a, $types)),
            'wf_first_pass' => KpiSql::countIf('('.$approved.') AND '.$this->workflowInstanceExists($a, $types, ' AND wi.edit_request_count = 0')),
            'approval_hours' => KpiSql::sumIf($approved, $approvalHours),
            'approval_measured' => KpiSql::countValue($approved, $approvalHours),
            'post_exec_returned' => KpiSql::countIf('('.$executed.') AND EXISTS (SELECT 1 FROM monthly_activity_approvals pa WHERE pa.monthly_activity_id = '.$a.'.id'
                ." AND pa.step = 'post_execution_review' AND pa.decision IN (".KpiSql::literalList(['clarification', 'rejected']).'))'),
            'verif_complete' => KpiSql::countIf('('.$closed.') AND '.$verifications('').' > 0 AND '.$verifications(" AND v.status = 'pending'").' = 0'),
            'verif_correct' => KpiSql::sumIf($closed, $verifications(" AND v.status = 'correct'")),
            'verif_resolved' => KpiSql::sumIf($closed, $verifications(' AND v.status IN ('.KpiSql::literalList(['correct', 'incorrect']).')')),
            'evaluated' => KpiSql::countIf('('.$closed.') AND EXISTS (SELECT 1 FROM activity_evaluations ev WHERE ev.monthly_activity_id = '.$a.'.id)'),
            'evaluation_score_sum' => KpiSql::sumIf($closed, $evaluationScore),
            'evaluation_score_count' => KpiSql::countValue($closed, $evaluationScore),
            'postexec_lag_days' => KpiSql::sumIf($eligible, $postExecutionLag),
            'postexec_lag_measured' => KpiSql::countValue($eligible, $postExecutionLag),
            'closure_days' => KpiSql::sumIf('('.$closed.') AND '.$a.'.actual_date IS NOT NULL', $closureLag),
            'closure_measured' => KpiSql::countValue('('.$closed.') AND '.$a.'.actual_date IS NOT NULL', $closureLag),
        ];

        // Execution-need decisions live in a JSON array (execution_needs_followup). JSON_SEARCH is
        // MySQL-only; elsewhere these metrics are undefined and the KPIs report "unavailable".
        $json = static fn (string $field, string $value): ?string => KpiSql::jsonArrayFieldCount($a.'.execution_needs_followup', $field, $value);
        $provided = $json('post_status', 'provided');
        $notProvided = $json('post_status', 'not_provided');
        $secured = $json('status', 'secured');
        $notSecured = $json('status', 'not_secured');
        $metrics['needs_provided'] = $provided === null ? null : KpiSql::sumIf($executed, $provided);
        $metrics['needs_assessed'] = $provided === null ? null : KpiSql::sumIf($executed, $provided.' + '.$notProvided);
        $metrics['needs_secured'] = $secured === null ? null : KpiSql::sumIf($approved, $secured);
        $metrics['needs_secured_assessed'] = $secured === null ? null : KpiSql::sumIf($approved, $secured.' + '.$notSecured);

        return $metrics;
    }

    /** Edit/delete requests counted by their own request date and branch (independent of plan versions). */
    protected function secondarySources(): array
    {
        $types = KpiWorkflowIdentity::sqlList('monthly');
        $source = function (string $table) use ($types): KpiMetricSource {
            return new KpiMetricSource(
                $table,
                fn (): Builder => DB::table($table.' as r')->whereRaw('r.entity_type IN ('.$types.')'),
                'r.requested_at',
                fn (?array $branchIds) => $branchIds === null ? null : ($branchIds === [] ? '1 = 0' : 'r.branch_id IN ('.KpiSql::intList($branchIds).')'),
                fn (Builder $query): string => 'r.branch_id',
                [
                    'change_requests' => 'COUNT(*)',
                    'change_approved' => KpiSql::countIf("r.status = 'approved'"),
                    'change_decided' => KpiSql::countIf('r.status IN ('.KpiSql::literalList(['approved', 'rejected']).')'),
                ]
            );
        };

        return [$source('monthly_plan_edit_requests'), $source('monthly_plan_delete_requests')];
    }
}
