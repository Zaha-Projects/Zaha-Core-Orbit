<?php

namespace App\Services\Kpi\Modules;

use App\Modules\Events\Models\Bazaar;
use App\Services\Kpi\Support\KpiSql;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BazaarKpiDefinition extends KpiModuleDefinition
{
    public function key(): string
    {
        return 'bazaar';
    }

    public function table(): string
    {
        return 'bazaars';
    }

    protected function dateColumn(): string
    {
        return 'bazaar_date';
    }

    /** Bazaars have no soft deletes or versioning: every row is live. */
    public function liveSql(string $alias): string
    {
        return '1 = 1';
    }

    protected function rawStageConditions(string $alias): array
    {
        $a = $alias;

        return [
            'planned' => $a.'.status <> '.KpiSql::literal(Bazaar::STATUS_DRAFT),
            'approved' => $a.'.status IN ('.KpiSql::literalList([Bazaar::STATUS_APPROVED, Bazaar::STATUS_EXECUTING, Bazaar::STATUS_POST_EXECUTION, Bazaar::STATUS_VERIFIED, Bazaar::STATUS_COMPLETED]).')',
            'executed' => $a.'.status IN ('.KpiSql::literalList([Bazaar::STATUS_POST_EXECUTION, Bazaar::STATUS_VERIFIED, Bazaar::STATUS_COMPLETED]).')',
            'closed' => $a.'.status = '.KpiSql::literal(Bazaar::STATUS_COMPLETED),
        ];
    }

    public function titleColumn(): string
    {
        return 'name';
    }

    /**
     * User-attributable Bazaar actions. Actors: relations_officer_id (set to the creating user),
     * bazaar_table_discounts.approved_by, verified_by (verification), closed_by (closure, since Phase 4).
     * Submission and execution actors are not recorded.
     */
    protected function actorSources(): array
    {
        $planned = $this->stageSql('planned', 's');
        $verification = KpiSql::daysBetween(KpiSql::dateOf('s.post_execution_submitted_at'), KpiSql::dateOf('s.verified_at'));

        return [
            $this->actorSource('u_created', fn (): Builder => $this->liveQuery('s'), 's.created_at', 's.relations_officer_id', [
                'u_created' => 'COUNT(*)',
                'u_created_submitted' => KpiSql::countIf($planned),
            ], ['action' => "'إنشاء بازار'", 'result' => 'CASE WHEN '.$planned." THEN 'مقدّم' ELSE 'مسودة' END"]),
            $this->actorSource('u_discounts', fn (): Builder => DB::table('bazaar_table_discounts as d')
                ->join('bazaar_tables as t', 't.id', '=', 'd.bazaar_table_id')
                ->join('bazaars as s', 's.id', '=', 't.bazaar_id')
                ->where('d.status', '<>', 'pending')
                ->whereNotNull('d.approved_by'),
                'd.requested_at', 'd.approved_by', [
                    'u_discount_decisions' => 'COUNT(*)',
                    'u_discount_approved' => KpiSql::countIf("d.status = 'approved'"),
                ], ['action' => "'قرار خصم على طاولة'", 'result' => self::labelCase('d.status', ['approved' => 'اعتماد', 'returned' => 'إعادة', 'rejected' => 'رفض'])]),
            $this->actorSource('u_verifications', fn (): Builder => DB::table('bazaars as s')->whereNotNull('s.verified_at'),
                's.verified_at', 's.verified_by', [
                    'u_bazaar_verified' => 'COUNT(*)',
                    'u_cases_handled' => 'COUNT(*)',
                    'u_cases_completed' => 'COUNT(*)',
                    'u_turnaround_days' => KpiSql::sumIf('TRUE', $verification),
                    'u_turnaround_measured' => KpiSql::countValue('TRUE', $verification),
                ], ['action' => "'التحقق من البازار'", 'result' => "'متحقق'", 'duration_days' => $verification]),
            $this->actorSource('u_closures', fn (): Builder => DB::table('bazaars as s')->whereNotNull('s.closed_at'),
                's.closed_at', 's.closed_by', ['u_closures' => 'COUNT(*)'], ['action' => "'إغلاق البازار'", 'result' => "'مغلق'"]),
        ];
    }

    public function notes(): array
    {
        return [
            'وقت الإغلاق يُسجَّل منذ المرحلة الرابعة فقط؛ البازارات المغلقة قبلها لا تملك وقت إغلاق.',
            'لا يوجد تاريخ تنفيذ فعلي للبازار؛ لذلك لا يدخل في مؤشر التنفيذ في الموعد.',
        ];
    }

    protected function mainMetrics(string $alias): array
    {
        $a = $alias;
        $planned = $this->stageSql('planned', $a);
        $approved = $this->stageSql('approved', $a);
        $executed = $this->stageSql('executed', $a);
        $closed = $this->stageSql('closed', $a);
        $reviewed = $a.'.status IN ('.KpiSql::literalList([Bazaar::STATUS_VERIFIED, Bazaar::STATUS_COMPLETED]).')';

        $tables = fn (string $condition): string => '(SELECT COUNT(*) FROM bazaar_tables t WHERE t.bazaar_id = '.$a.'.id AND t.was_booked = TRUE'.$condition.')';
        $tableSum = fn (string $expression, string $condition = ''): string => '(SELECT COALESCE(SUM('.$expression.'), 0) FROM bazaar_tables t WHERE t.bazaar_id = '.$a.'.id AND t.was_booked = TRUE'.$condition.')';
        $paid = 'COALESCE(t.amount_paid, 0)';
        $discounts = fn (string $condition): string => '(SELECT COUNT(*) FROM bazaar_table_discounts d JOIN bazaar_tables t ON t.id = d.bazaar_table_id WHERE t.bazaar_id = '.$a.'.id'.$condition.')';
        $approvalHours = KpiSql::hoursBetween($a.'.submitted_at', $a.'.approved_at');
        $verificationHours = KpiSql::hoursBetween($a.'.post_execution_submitted_at', $a.'.verified_at');
        $closureLag = KpiSql::daysBetween($a.'.bazaar_date', KpiSql::dateOf($a.'.closed_at'));

        return [
            'tables_planned' => KpiSql::sumIf($executed, $a.'.planned_table_count'),
            'tables_booked' => KpiSql::sumIf($executed, 'COALESCE('.$a.'.actual_occupied_table_count, 0)'),
            'booked_tables' => KpiSql::sumIf($executed, $tables('')),
            'renter_matches' => KpiSql::sumIf($executed, $tables(' AND t.actual_renter_matches = TRUE')),
            'material_assessed' => KpiSql::sumIf($executed, $tables(' AND t.material_matches IS NOT NULL')),
            'material_matches' => KpiSql::sumIf($executed, $tables(' AND t.material_matches = TRUE')),
            // Payment state is derived from amounts: payment_status is chosen freely and can be overwritten by discounts.
            'amount_due' => KpiSql::sumIf($executed, $tableSum('t.amount_due')),
            'amount_paid' => KpiSql::sumIf($executed, $tableSum($paid)),
            'planned_rent' => KpiSql::sumIf($executed, $tableSum('t.planned_rental_amount')),
            'tables_paid' => KpiSql::sumIf($executed, $tables(' AND t.amount_due > 0 AND '.$paid.' >= t.amount_due')),
            'tables_partial' => KpiSql::sumIf($executed, $tables(' AND t.amount_due > 0 AND '.$paid.' > 0 AND '.$paid.' < t.amount_due')),
            'tables_unpaid' => KpiSql::sumIf($executed, $tables(' AND t.amount_due > 0 AND '.$paid.' = 0')),
            'tables_unpaid_partial' => KpiSql::sumIf($executed, $tables(' AND t.amount_due > 0 AND '.$paid.' < t.amount_due')),
            'outstanding' => KpiSql::sumIf($executed, $tableSum('t.amount_due - '.$paid, ' AND '.$paid.' < t.amount_due')),
            'discount_requests' => KpiSql::sumIf($planned, $discounts('')),
            'discount_pending' => KpiSql::sumIf($planned, $discounts(" AND d.status = 'pending'")),
            'discount_approved' => KpiSql::sumIf($planned, $discounts(" AND d.status = 'approved'")),
            'discount_returned' => KpiSql::sumIf($planned, $discounts(" AND d.status = 'returned'")),
            'discount_rejected' => KpiSql::sumIf($planned, $discounts(" AND d.status = 'rejected'")),
            'discount_decided' => KpiSql::sumIf($planned, $discounts(" AND d.status <> 'pending'")),
            'discount_value' => KpiSql::sumIf($planned, '(SELECT COALESCE(SUM(d.original_amount - d.final_amount), 0) FROM bazaar_table_discounts d JOIN bazaar_tables t ON t.id = d.bazaar_table_id'
                .' WHERE t.bazaar_id = '.$a.".id AND d.status = 'approved' AND d.final_amount IS NOT NULL)"),
            'reviewed' => KpiSql::countIf('('.$executed.') AND '.$reviewed),
            'approval_hours' => KpiSql::sumIf($approved, $approvalHours),
            'approval_measured' => KpiSql::countValue($approved, $approvalHours),
            'verification_hours' => KpiSql::sumIf($reviewed, $verificationHours),
            'verification_measured' => KpiSql::countValue($reviewed, $verificationHours),
            'closure_days' => KpiSql::sumIf($closed, $closureLag),
            'closure_measured' => KpiSql::countValue($closed, $closureLag),
            'closure_missing' => KpiSql::countIf('('.$closed.') AND '.$a.'.closed_at IS NULL'),
        ];
    }
}
