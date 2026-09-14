<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\MonthlyActivity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityCalendarController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function calendar(Request $request)
    {
        $user = $request->user();
        $year = $this->normalizeMonthlyIndexYear($request->input('year'));
        $month = $this->normalizeMonthlyIndexMonth($request->input('month'));
        $viewScope = $request->input('scope', 'default');
        $selectedStatus = trim((string) $request->input('status', ''));
        $selectedBranchId = filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;

        if ($followupBranchId = $this->followupOfficerBranchId($user)) {
            $selectedBranchId = $followupBranchId;
            $viewScope = 'default';
        }

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($user)) {
            abort(403);
        }

        $query = MonthlyActivity::query()
            ->with(['branch', 'agendaEvent'])
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $query->where(function ($dateQuery) use ($year, $month) {
            $dateQuery
                ->where(function ($proposedDateQuery) use ($year, $month) {
                    $proposedDateQuery
                        ->whereNotNull('proposed_date')
                        ->whereYear('proposed_date', $year)
                        ->whereMonth('proposed_date', $month);
                })
                ->orWhere(function ($fallbackMonthQuery) use ($month) {
                    $fallbackMonthQuery
                        ->whereNull('proposed_date')
                        ->where('month', $month);
                });
        });

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($query, $user);
        }
        $this->applyDraftVisibilityScope($query, $user);
        $this->applyVolunteerCoordinatorVisibilityScope($query, $user);
        $this->applyMonthlyPageStatusFilter($query, $selectedStatus);

        if ($viewScope === 'all_branches') {
            $this->applyOtherBranchesScope($query, $user);

            $query
                ->where('status', 'approved')
                ->where(function ($approvalQuery) {
                    $approvalQuery
                        ->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $items = $query->orderBy('month')
            ->orderBy('day')
            ->orderBy('proposed_date')
            ->get()
            ->map(function (MonthlyActivity $activity) use ($year, $request, $viewScope) {
            $isReadOnlyUnified = $this->isReadOnlyUnifiedAgendaActivity($activity);
            $canBranchPartialEditUnified = $this->canBranchEditUnifiedNonCoreFields($activity, $request->user());
            $canCompleteAfterExecution = $this->canCompleteAfterExecution($activity, $request->user());
            $canOpenEdit = $this->canUseMonthlyActivityEditRoute($request->user());

            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'date' => optional($activity->proposed_date)->format('Y-m-d')
                    ?? sprintf('%04d-%02d-%02d', $year, $activity->month, $activity->day),
                'branch' => $activity->branch?->name,
                'status' => $activity->status,
                'source_label' => $activity->is_in_agenda
                    ? __('app.roles.programs.monthly_activities.sources.from_agenda')
                    : __('app.roles.programs.monthly_activities.sources.manual'),
                'event_type' => $activity->agendaEvent?->event_type,
                'event_type_label' => $activity->agendaEvent?->event_type
                    ? __('app.roles.relations.agenda.types.' . $activity->agendaEvent->event_type)
                    : null,
                'plan_type' => $activity->plan_type,
                'plan_type_label' => $activity->plan_type
                    ? __('app.roles.relations.agenda.plans.' . $activity->plan_type)
                    : null,
                'plan_version' => (int) ($activity->plan_version ?: 1),
                'requires_workshops' => (bool) $activity->requires_workshops,
                'requires_communications' => (bool) $activity->requires_communications,
                'edit_url' => route('role.relations.activities.edit', $activity),
                'post_execution_url' => $canCompleteAfterExecution
                    ? route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post'])
                    : null,
                'can_complete_after_execution' => $canCompleteAfterExecution,
                'open_url' => $viewScope === 'all_branches'
                    ? route('role.relations.activities.show', $activity)
                    : (($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                        ? route('role.relations.activities.show', $activity)
                        : ($canOpenEdit ? route('role.relations.activities.edit', $activity) : route('role.relations.activities.show', $activity))),
                'read_only_unified' => $isReadOnlyUnified && ! $canBranchPartialEditUnified,
            ];
            })->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'items' => $items,
        ]);
    }
}
