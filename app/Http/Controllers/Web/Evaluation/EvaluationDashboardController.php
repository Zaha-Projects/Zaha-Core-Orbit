<?php

namespace App\Http\Controllers\Web\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvaluation;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\PostExecutionVerification;
use Illuminate\Http\Request;

class EvaluationDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $activityQuery = MonthlyActivity::query()->where('is_archived', false);
        $verificationQuery = PostExecutionVerification::query();
        $evaluationQuery = ActivityEvaluation::query();
        $scopeBranchIds = null;
        if (! $user->can('evaluation.view_all')) {
            $ids = $user->scopedBranchIds();
            abort_if(count($ids) !== 1, 403, __('evaluation.validation.single_branch'));
            $activityQuery->whereIn('branch_id', $ids);
            $verificationQuery->whereIn('branch_id', $ids);
            $evaluationQuery->whereIn('branch_id', $ids);
            $scopeBranchIds = $ids;
        }
        $stats = [
            'activities' => (clone $activityQuery)->count(),
            'branches' => $user->can('evaluation.view_all') ? Branch::count() : 1,
            'pending_verification' => (clone $verificationQuery)->where('status', 'pending')->distinct()->count('monthly_activity_id'),
            'incorrect' => (clone $verificationQuery)->where('status', 'incorrect')->distinct()->count('monthly_activity_id'),
            'pending_evaluation' => (clone $activityQuery)->doesntHave('activityEvaluation')->whereNotNull('post_execution_payload')->count(),
            'evaluated' => (clone $evaluationQuery)->count(),
            'average' => round((float) (clone $evaluationQuery)->avg('normalized_score'), 2),
            'this_month' => (clone $evaluationQuery)->whereBetween('submitted_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];
        $latest = (clone $evaluationQuery)->with(['activity.eventType', 'branch', 'evaluator', 'form'])->latest('submitted_at')->limit(8)->get();
        $lowScores = (clone $evaluationQuery)->with(['activity', 'branch'])->where('normalized_score', '<', 5)->orderBy('normalized_score')->limit(6)->get();
        $verificationSummary = (clone $verificationQuery)->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');
        $branchPerformance = ActivityEvaluation::query()
            ->when($scopeBranchIds, fn ($query, $ids) => $query->whereIn('branch_id', $ids))
            ->select('branch_id')
            ->selectRaw('ROUND(AVG(normalized_score), 2) as average_score')
            ->selectRaw('COUNT(*) as evaluations_count')
            ->with('branch:id,name,city,color_hex')
            ->groupBy('branch_id')
            ->orderByDesc('average_score')
            ->get();
        $monthlyTrend = ActivityEvaluation::query()
            ->when($scopeBranchIds, fn ($query, $ids) => $query->whereIn('branch_id', $ids))
            ->where('submitted_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['submitted_at', 'normalized_score'])
            ->groupBy(fn (ActivityEvaluation $evaluation) => $evaluation->submitted_at->format('Y-m'))
            ->map(fn ($items, string $period) => (object) [
                'period' => $period,
                'average_score' => round((float) $items->avg('normalized_score'), 2),
                'evaluations_count' => $items->count(),
            ])->sortBy('period')->values();
        $branchPending = MonthlyActivity::query()->notArchived()->hasPostExecution()->doesntHave('activityEvaluation')
            ->when($scopeBranchIds, fn ($query, $ids) => $query->whereIn('branch_id', $ids))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->select('branch_id')->selectRaw('COUNT(*) as pending_count')
            ->with('branch:id,name,city')->groupBy('branch_id')->orderByDesc('pending_count')->limit(8)->get();
        $evaluatable = $stats['evaluated'] + $stats['pending_evaluation'];
        $completionRate = $evaluatable > 0 ? round(($stats['evaluated'] / $evaluatable) * 100, 1) : 0;

        return view('pages.evaluation.dashboard', compact(
            'user', 'stats', 'latest', 'lowScores', 'verificationSummary', 'branchPerformance',
            'monthlyTrend', 'branchPending', 'completionRate'
        ));
    }
}
