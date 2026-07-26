<?php

namespace App\Http\Controllers\Web\Evaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitActivityEvaluationRequest;
use App\Http\Requests\UpdateEvaluationVisibilityRequest;
use App\Http\Requests\VerifyPostExecutionRequest;
use App\Models\ActivityEvaluation;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\EvaluationForm;
use App\Models\MonthlyActivity;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class ActivityEvaluationsController extends Controller
{
    public function index(Request $request)
    {
        $query = MonthlyActivity::query()->with(['branch', 'creator', 'activityEvaluation.evaluator'])->where('is_archived', false);
        if (! $request->user()->can('evaluation.view_all')) $query->whereIn('branch_id', $request->user()->scopedBranchIds());
        $query->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('has_evaluation') === 'yes', fn ($q) => $q->has('activityEvaluation'))
            ->when($request->input('has_evaluation') === 'no', fn ($q) => $q->doesntHave('activityEvaluation'));
        return view('pages.evaluation.index', ['activities' => $query->latest('id')->paginate(20)->withQueryString(), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function review(MonthlyActivity $monthlyActivity, ActivityEvaluationService $service)
    {
        $this->authorize('verify', $monthlyActivity);
        $service->synchronizeVerificationFields($monthlyActivity);
        return view('pages.evaluation.verify', ['activity' => $monthlyActivity->load(['branch', 'team', 'attachments', 'postExecutionVerifications.verifier'])]);
    }

    public function verify(VerifyPostExecutionRequest $request, MonthlyActivity $monthlyActivity, ActivityEvaluationService $service)
    {
        $service->verify($monthlyActivity, $request->user(), $request->validated()['items']);
        return back()->with('success', __('evaluation.messages.verification_saved'));
    }

    public function create(MonthlyActivity $monthlyActivity)
    {
        $this->authorize('submit', $monthlyActivity);
        $form = EvaluationForm::query()->where('is_active', true)->with(['questions' => fn ($q) => $q->where('is_active', true)])->firstOrFail();
        return view('pages.evaluation.create', compact('monthlyActivity', 'form'));
    }

    public function store(SubmitActivityEvaluationRequest $request, MonthlyActivity $monthlyActivity, ActivityEvaluationService $service)
    {
        $validated = $request->validated();
        $form = EvaluationForm::query()->where('is_active', true)->findOrFail((int) $validated['evaluation_form_id']);
        $evaluation = $service->submit($monthlyActivity, $form, $request->user(), $validated['answers'], $validated['notes'] ?? null);
        return redirect()->route('evaluations.show', $evaluation)->with('success', __('evaluation.messages.submitted'));
    }

    public function show(ActivityEvaluation $activityEvaluation)
    {
        $this->authorize('view', $activityEvaluation);
        return view('pages.evaluation.show', ['evaluation' => $activityEvaluation->load(['activity.branch', 'form', 'answers', 'evaluator', 'activity.postExecutionVerifications.verifier'])]);
    }

    public function updateVisibility(UpdateEvaluationVisibilityRequest $request, ActivityEvaluation $activityEvaluation)
    {
        $old = $activityEvaluation->visibility;
        $activityEvaluation->update(['visibility' => $request->validated()['visibility'], 'visibility_updated_by' => $request->user()->id, 'visibility_updated_at' => now()]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'evaluation_visibility_updated', 'module' => 'evaluation', 'entity_type' => ActivityEvaluation::class, 'entity_id' => $activityEvaluation->id, 'old_values' => ['visibility' => $old], 'new_values' => ['visibility' => $activityEvaluation->visibility]]);
        return back()->with('success', __('evaluation.messages.visibility_updated'));
    }
}
