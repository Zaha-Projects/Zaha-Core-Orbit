<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use Illuminate\Http\Request;
use App\Services\PlanChangeRequestWorkflowService;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivityApprovals;

class MonthlyActivityChangeRequestDecisionController extends Controller
{
    use InteractsWithMonthlyActivityApprovals;
    public function decideDeleteRequest(Request $request, MonthlyPlanDeleteRequest $deleteRequest, PlanChangeRequestWorkflowService $changeRequests)
    {
        $this->abortIfProgramsManagerViewOnly($request->user());

        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'required_if:decision,rejected'],
            'focus_areas' => ['nullable', 'array'],
            'focus_areas.*' => ['string', Rule::in(array_keys($this->focusAreaLabels()))],
        ]);

        if ($data['decision'] === 'rejected' && empty($data['focus_areas'])) {
            return back()->withErrors(['focus_areas' => 'يرجى تحديد القسم المرتبط بسبب الرفض.'])->withInput();
        }

        $comment = $this->formatDecisionComment($data['comment'] ?? null, $data['focus_areas'] ?? []);

        $changeRequests->decide($deleteRequest, 'monthly_activities', $request->user(), $data['decision'], $comment);

        return redirect()->route('role.programs.approvals.index', ['tab' => 'delete'])->with('status', 'تم تحديث قرار طلب الحذف.');
    }

    public function decideEditRequest(Request $request, MonthlyPlanEditRequest $editRequest, PlanChangeRequestWorkflowService $changeRequests)
    {
        $this->abortIfProgramsManagerViewOnly($request->user());

        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'required_if:decision,rejected'],
            'focus_areas' => ['nullable', 'array'],
            'focus_areas.*' => ['string', Rule::in(array_keys($this->focusAreaLabels()))],
        ]);

        if ($data['decision'] === 'rejected' && empty($data['focus_areas'])) {
            return back()->withErrors(['focus_areas' => 'يرجى تحديد القسم المرتبط بسبب الرفض.'])->withInput();
        }

        $comment = $this->formatDecisionComment($data['comment'] ?? null, $data['focus_areas'] ?? []);

        $changeRequests->decide($editRequest, 'monthly_activities', $request->user(), $data['decision'], $comment);

        return redirect()->route('role.programs.approvals.index', ['tab' => 'edit'])->with('status', 'تم تحديث قرار طلب التعديل.');
    }
}
