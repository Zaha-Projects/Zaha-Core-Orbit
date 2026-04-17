<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;

class EnterpriseAnalyticsService
{
    public function build(int $year): array
    {
        $agenda = AgendaEvent::query()->whereYear('event_date', $year)->notArchived();
        $monthly = MonthlyActivity::query()->whereYear('proposed_date', $year)->notArchived();

        $totalEvents = (clone $agenda)->count();
        $approvedEvents = (clone $agenda)->where('status', 'published')->count();
        $rejectedEvents = (clone $agenda)->where('status', 'rejected')->count();
        $pendingApprovals = (clone $agenda)->whereIn('status', ['submitted', 'changes_requested'])->count();
        $executedActivities = (clone $monthly)->whereNotNull('actual_date')->count();

        $branchesCount = max(1, Branch::count());
        $activeBranches = Branch::query()->whereHas('monthlyActivities', fn ($q) => $q->whereYear('proposed_date', $year))->count();
        $branchParticipationRate = round(($activeBranches / $branchesCount) * 100, 1);
        $planAdherence = round(($executedActivities / max(1, (clone $monthly)->count())) * 100, 1);

        return [
            'kpis' => compact('totalEvents', 'approvedEvents', 'rejectedEvents', 'pendingApprovals', 'executedActivities', 'branchParticipationRate', 'planAdherence'),
            'monthlyTrend' => (clone $agenda)->selectRaw('month, COUNT(*) as total')->groupBy('month')->orderBy('month')->pluck('total', 'month'),
            'approvalRatio' => [
                'approved' => $approvedEvents,
                'rejected' => $rejectedEvents,
            ],
            'eventsPerDepartment' => (clone $agenda)->selectRaw('COALESCE(department_id, 0) as department_id, COUNT(*) as total')->groupBy('department_id')->pluck('total', 'department_id'),
            'eventsPerBranch' => AgendaEvent::query()->whereYear('event_date', $year)
                ->join('agenda_participations', 'agenda_participations.agenda_event_id', '=', 'agenda_events.id')
                ->where('agenda_participations.entity_type', 'branch')
                ->selectRaw('agenda_participations.entity_id as branch_id, COUNT(*) as total')
                ->groupBy('agenda_participations.entity_id')
                ->pluck('total', 'branch_id'),
            'activityCompletionRate' => [
                'completed' => $executedActivities,
                'open' => max(0, (clone $monthly)->count() - $executedActivities),
            ],
        ];
    }

    public function branchMetrics(int $year)
    {
        return Branch::query()->withCount([
            'monthlyActivities as total_activities' => fn ($q) => $q->whereYear('proposed_date', $year),
            'monthlyActivities as completed_activities' => fn ($q) => $q->whereYear('proposed_date', $year)->whereNotNull('actual_date'),
            'monthlyActivities as approved_activities' => fn ($q) => $q->whereYear('proposed_date', $year)->where('status', 'approved'),
        ])->get()->map(function ($branch) {
            $total = max(1, $branch->total_activities);
            return [
                'branch' => $branch->name,
                'total_events_participated' => $branch->total_activities,
                'participation_rate' => round(($branch->total_activities / $total) * 100, 1),
                'approval_success_rate' => round(($branch->approved_activities / $total) * 100, 1),
                'activity_completion_rate' => round(($branch->completed_activities / $total) * 100, 1),
            ];
        });
    }
}
