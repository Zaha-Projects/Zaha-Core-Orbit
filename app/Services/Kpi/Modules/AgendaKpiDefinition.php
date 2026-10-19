<?php

namespace App\Services\Kpi\Modules;

use App\Services\Kpi\Support\KpiMetricSource;
use App\Services\Kpi\Support\KpiSql;
use App\Services\Kpi\Support\KpiWorkflowIdentity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AgendaKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'agenda';
    }

    public function table(): string
    {
        return 'agenda_events';
    }

    protected function dateColumn(): string
    {
        return 'event_date';
    }

    /** Live = not soft-deleted and not archived (an approved edit archives its source version). */
    public function liveSql(string $alias): string
    {
        return $alias.'.deleted_at IS NULL AND '.$alias.'.is_archived = FALSE AND '.$alias.".status <> 'archived'";
    }

    protected function rawStageConditions(string $alias): array
    {
        return [
            'planned' => $alias.".status <> 'draft'",
            // Final approval publishes the event; relations_approved is only a partial step.
            'approved' => $alias.".status = 'published'",
            'executed' => null,
            'closed' => null,
        ];
    }

    public function titleColumn(): string
    {
        return 'event_name';
    }

    public function hasBranchColumn(): bool
    {
        return false;
    }

    public function unavailableReason(string $stage): ?string
    {
        return in_array($stage, ['executed', 'closed'], true)
            ? 'الأجندة السنوية لا تمتلك مرحلة تنفيذ أو إغلاق؛ يتم التنفيذ عبر الأنشطة الشهرية المرتبطة.'
            : null;
    }

    public function notes(): array
    {
        return [
            'فعاليات الأجندة بدون تاريخ محدد لا تدخل ضمن أي فترة.',
            'نطاق الفرع للأجندة يعتمد على مشاركة الفرع كـ«مشارك» في الفعالية.',
        ];
    }

    /** Agenda events have no branch_id: a branch is in scope when it participates. */
    public function branchScopeSql(string $alias, ?array $branchIds)
    {
        if ($branchIds === null) {
            return null;
        }
        if ($branchIds === []) {
            return '1 = 0';
        }

        return 'EXISTS (SELECT 1 FROM agenda_participations scope_ap WHERE scope_ap.agenda_event_id = '.$alias.'.id'
            ." AND scope_ap.entity_type = 'branch' AND scope_ap.participation_status = 'participant'"
            .' AND scope_ap.entity_id IN ('.KpiSql::intList($branchIds).'))';
    }

    public function branchKey(Builder $query, string $alias): string
    {
        $query->join('agenda_participations as group_ap', function ($join) use ($alias): void {
            $join->on('group_ap.agenda_event_id', '=', $alias.'.id')
                ->where('group_ap.entity_type', '=', 'branch')
                ->where('group_ap.participation_status', '=', 'participant');
        });

        return 'group_ap.entity_id';
    }

    protected function mainMetrics(string $alias): array
    {
        $a = $alias;
        $planned = $this->stageSql('planned', $a);
        $approved = $this->stageSql('approved', $a);
        $types = KpiWorkflowIdentity::sqlList('agenda');
        $firstSubmission = '(SELECT MIN(wl.acted_at) FROM workflow_logs wl JOIN workflow_instances wi ON wi.id = wl.workflow_instance_id'
            .' JOIN workflow_steps st ON st.id = wl.workflow_step_id WHERE wi.entity_type IN ('.$types.') AND wi.entity_id = '.$a.'.id'
            ." AND st.step_key = 'agenda_relations_officer_submit')";
        $approvalHours = KpiSql::hoursBetween($firstSubmission, $a.'.approved_by_executive_at');

        return [
            'wf_planned' => KpiSql::countIf($planned),
            'wf_returned' => KpiSql::countIf('('.$planned.') AND EXISTS (SELECT 1 FROM agenda_approvals aa WHERE aa.agenda_event_id = '.$a.".id AND aa.decision = 'changes_requested')"),
            'approval_hours' => KpiSql::sumIf($approved, $approvalHours),
            'approval_measured' => KpiSql::countValue($approved, $approvalHours),
        ];
    }

    /**
     * User-attributable Agenda actions. Actors: created_by, workflow_logs.acted_by on the
     * submission step (agenda_relations_officer_submit) and on main approval steps.
     */
    protected function actorSources(): array
    {
        $types = KpiWorkflowIdentity::sqlList('agenda');
        $planned = $this->stageSql('planned', 's');
        $approved = $this->stageSql('approved', 's');
        $returned = '('.$planned.') AND EXISTS (SELECT 1 FROM agenda_approvals aa WHERE aa.agenda_event_id = s.id AND aa.decision = \'changes_requested\')';
        $firstSubmission = '(SELECT MIN(fw.acted_at) FROM workflow_logs fw JOIN workflow_instances fi ON fi.id = fw.workflow_instance_id'
            .' JOIN workflow_steps fs ON fs.id = fw.workflow_step_id WHERE fi.entity_type IN ('.$types.') AND fi.entity_id = s.id'
            ." AND fs.step_key = 'agenda_relations_officer_submit')";
        $createToSubmit = KpiSql::hoursBetween('s.created_at', $firstSubmission);
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
            ], ['action' => "'إنشاء فعالية أجندة'", 'result' => $statusLabel, 'duration_hours' => $createToSubmit], ['returned' => $returned]),
            $this->actorSource('u_submissions', fn (): Builder => DB::table('workflow_logs as wl')
                ->join('workflow_instances as wi', 'wi.id', '=', 'wl.workflow_instance_id')
                ->join('workflow_steps as st', 'st.id', '=', 'wl.workflow_step_id')
                ->join('agenda_events as s', 's.id', '=', 'wi.entity_id')
                ->whereRaw('wi.entity_type IN ('.$types.')')
                ->where('st.step_key', 'agenda_relations_officer_submit'),
                'wl.acted_at', 'wl.acted_by', [
                    'u_submitted' => 'COUNT(DISTINCT wi.entity_id)',
                    'u_resubmissions' => '(COUNT(*) - COUNT(DISTINCT wi.entity_id))',
                ], ['action' => "'تقديم للاعتماد'"]),
            $this->decisionSource($firstSubmission, false),
        ];
    }

    protected function secondarySources(): array
    {
        $monthly = new MonthlyActivityKpiDefinition();
        $today = KpiSql::literal(now()->toDateString());
        $participant = "ap.participation_status = 'participant'";
        $monthlyExists = fn (string $extra): string => 'EXISTS (SELECT 1 FROM monthly_activities m WHERE m.agenda_event_id = s.id AND m.branch_id = ap.entity_id AND '
            .$monthly->liveSql('m').$extra.')';
        $due = $participant." AND s.status = 'published' AND s.event_date <= ".$today;
        $window = $participant." AND s.plan_type = 'non_unified' AND ap.proposed_date IS NOT NULL AND s.event_date IS NOT NULL";

        // Counting unit: one agenda participation (event × branch). The branch's realisation is its
        // Monthly activity, which is counted in the Monthly module, never added to the agenda count.
        $participations = new KpiMetricSource(
            'participations',
            fn (): Builder => DB::table('agenda_participations as ap')
                ->join('agenda_events as s', 's.id', '=', 'ap.agenda_event_id')
                ->whereRaw($this->liveSql('s'))
                ->where('ap.entity_type', 'branch'),
            's.event_date',
            fn (?array $branchIds) => $branchIds === null ? null : ($branchIds === [] ? '1 = 0' : 'ap.entity_id IN ('.KpiSql::intList($branchIds).')'),
            fn (Builder $query): string => 'ap.entity_id',
            [
                'part_rows' => 'COUNT(*)',
                'part_answered' => KpiSql::countIf('ap.participation_status IN ('.KpiSql::literalList(['participant', 'not_participant']).')'),
                'participants' => KpiSql::countIf($participant),
                'participants_with_plan' => KpiSql::countIf($participant.' AND '.$monthlyExists('')),
                'participants_due' => KpiSql::countIf($due),
                'participants_executed' => KpiSql::countIf($due.' AND '.$monthlyExists(' AND ('.$monthly->stageSql('executed', 'm').')')),
                'part_window_eligible' => KpiSql::countIf($window),
                'part_window_ok' => KpiSql::countIf($window.' AND ABS('.KpiSql::daysBetween('s.event_date', 'ap.proposed_date').') <= 7'),
            ]
        );

        // Agenda change requests carry no branch: available to oversight viewers only.
        $requestTypes = KpiWorkflowIdentity::sqlList('agenda');
        $requests = function (string $table) use ($requestTypes): KpiMetricSource {
            return new KpiMetricSource(
                $table,
                fn (): Builder => DB::table($table.' as r')->whereRaw('r.entity_type IN ('.$requestTypes.')'),
                'r.requested_at',
                fn (?array $branchIds) => $branchIds === null ? null : false,
                fn (Builder $query): string => 'NULL',
                [
                    'change_requests' => 'COUNT(*)',
                    'change_approved' => KpiSql::countIf("r.status = 'approved'"),
                    'change_decided' => KpiSql::countIf('r.status IN ('.KpiSql::literalList(['approved', 'rejected']).')'),
                ]
            );
        };

        return [$participations, $requests('annual_agenda_edit_requests'), $requests('annual_agenda_delete_requests')];
    }
}
