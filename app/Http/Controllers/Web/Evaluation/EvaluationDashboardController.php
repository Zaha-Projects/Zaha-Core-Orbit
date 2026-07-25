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
        if (! $user->can('evaluation.view_all')) {
            $ids = $user->scopedBranchIds();
            abort_if(count($ids) !== 1, 403, __('evaluation.validation.single_branch'));
            $activityQuery->whereIn('branch_id', $ids);
            $verificationQuery->whereIn('branch_id', $ids);
            $evaluationQuery->whereIn('branch_id', $ids);
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
        $latest = (clone $evaluationQuery)->with(['activity', 'branch', 'evaluator'])->latest('submitted_at')->limit(10)->get();
        return view('pages.evaluation.dashboard', compact('stats', 'latest'));
    }
}
