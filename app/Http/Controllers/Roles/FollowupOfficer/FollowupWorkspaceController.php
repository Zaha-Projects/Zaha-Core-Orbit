<?php

namespace App\Http\Controllers\Roles\FollowupOfficer;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvaluation;
use App\Models\EvaluationForm;
use App\Models\MonthlyActivity;
use App\Models\PostExecutionVerification;
use App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesController;
use Illuminate\Http\Request;

class FollowupWorkspaceController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user()->loadMissing('branch');
        $branchIds = $this->branchIds($user);
        $activities = MonthlyActivity::query()->notArchived()->whereIn('branch_id', $branchIds);
        $evaluations = ActivityEvaluation::query()->whereIn('branch_id', $branchIds);
        $verifications = PostExecutionVerification::query()->whereIn('branch_id', $branchIds);
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats = [
            'month_plans' => (clone $activities)->whereBetween('proposed_date', [$monthStart, $monthEnd])->count(),
            'post_completed' => (clone $activities)->hasPostExecution()->count(),
            'awaiting_verification' => (clone $verifications)->where('status', 'pending')->distinct()->count('monthly_activity_id'),
            'ready_evaluation' => (clone $activities)->awaitingEvaluation()->whereHas('postExecutionVerifications')->whereDoesntHave('postExecutionVerifications', fn ($q) => $q->where('status', 'pending'))->count(),
            'evaluated_month' => (clone $evaluations)->whereBetween('submitted_at', [$monthStart, $monthEnd])->count(),
            'branch_average' => round((float) (clone $evaluations)->avg('normalized_score'), 2),
        ];

        $workflow = [
            'planned' => (clone $activities)->count(),
            'executed' => (clone $activities)->where(fn ($q) => $q->where('execution_status', 'executed')->orWhereNotNull('actual_date'))->count(),
            'post_completed' => $stats['post_completed'],
            'awaiting_review' => $stats['awaiting_verification'],
            'ready' => $stats['ready_evaluation'],
            'evaluated' => (clone $evaluations)->count(),
        ];

        $urgent = (clone $activities)->awaitingEvaluation()
            ->with(['eventType', 'postExecutionVerifications'])
            ->withCount([
                'postExecutionVerifications as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'postExecutionVerifications as incorrect_count' => fn ($q) => $q->where('status', 'incorrect'),
            ])->orderByDesc('incorrect_count')->orderByDesc('pending_count')->limit(8)->get();
        $recentEvaluations = (clone $evaluations)->with(['activity.eventType', 'evaluator'])->latest('submitted_at')->limit(6)->get();
        $upcoming = (clone $activities)->with('eventType')->whereDate('proposed_date', '>=', today())->orderBy('proposed_date')->limit(8)->get();
        $verificationSummary = (clone $verifications)->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');

        return view('roles.followup_officer.dashboard', compact('user', 'stats', 'workflow', 'urgent', 'recentEvaluations', 'upcoming', 'verificationSummary'));
    }

    public function monthlyPlans(Request $request)
    {
        return app(MonthlyActivitiesController::class)->index($request);
    }

    public function showPlan(Request $request, MonthlyActivity $monthlyActivity)
    {
        $this->authorize('viewActivity', $monthlyActivity);
        return view('roles.followup_officer.plan-details', ['activity' => $monthlyActivity->load(['branch', 'eventType', 'targetGroup', 'creator', 'agendaEvent.department', 'team', 'attachments.uploader', 'approvals.approver', 'postExecutionVerifications.verifier', 'activityEvaluation'])]);
    }

    public function awaitingEvaluation(Request $request)
    {
        $filters = $request->validate(['relationship' => ['nullable', 'in:all,mine'], 'status' => ['nullable', 'string', 'max:50']]);
        $query = MonthlyActivity::query()->notArchived()->forFollowupOfficer($request->user())->awaitingEvaluation()
            ->with(['branch', 'eventType', 'creator', 'agendaEvent.department'])
            ->withCount([
                'postExecutionVerifications as verification_total',
                'postExecutionVerifications as verified_count' => fn ($q) => $q->whereIn('status', ['correct', 'incorrect']),
                'postExecutionVerifications as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'postExecutionVerifications as incorrect_count' => fn ($q) => $q->where('status', 'incorrect'),
            ]);
        if (($filters['relationship'] ?? 'all') === 'mine') $query->forUserRelationship($request->user());
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        return view('roles.followup_officer.awaiting-evaluation', ['activities' => $query->latest('updated_at')->paginate(15)->withQueryString(), 'filters' => $filters]);
    }

    public function evaluations(Request $request)
    {
        $filters = $request->validate([
            'activity' => ['nullable', 'string', 'max:255'], 'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2100'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'score_min' => ['nullable', 'numeric', 'between:1,10'], 'score_max' => ['nullable', 'numeric', 'between:1,10'],
            'form_id' => ['nullable', 'integer', 'exists:evaluation_forms,id'], 'visibility' => ['nullable', 'in:branch_only,authorized_users'],
        ]);
        $query = ActivityEvaluation::query()->whereIn('branch_id', $this->branchIds($request->user()))
            ->with(['activity.eventType', 'activity.agendaEvent.department', 'form', 'evaluator']);
        $query->when($filters['activity'] ?? null, fn ($q, $value) => $q->whereHas('activity', fn ($a) => $a->where('title', 'like', '%'.$value.'%')))
            ->when($filters['month'] ?? null, fn ($q, $value) => $q->whereMonth('submitted_at', $value))
            ->when($filters['year'] ?? null, fn ($q, $value) => $q->whereYear('submitted_at', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('submitted_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('submitted_at', '<=', $value))
            ->when($filters['score_min'] ?? null, fn ($q, $value) => $q->where('normalized_score', '>=', $value))
            ->when($filters['score_max'] ?? null, fn ($q, $value) => $q->where('normalized_score', '<=', $value))
            ->when($filters['form_id'] ?? null, fn ($q, $value) => $q->where('evaluation_form_id', $value))
            ->when($filters['visibility'] ?? null, fn ($q, $value) => $q->where('visibility', $value));
        return view('roles.followup_officer.evaluations', ['evaluations' => $query->latest('submitted_at')->paginate(15)->withQueryString(), 'filters' => $filters, 'forms' => EvaluationForm::orderBy('name_ar')->get()]);
    }

    private function branchIds($user): array
    {
        $ids = $user->scopedBranchIds();
        abort_if(count($ids) !== 1, 403, __('evaluation.validation.single_branch'));
        return $ids;
    }
}
