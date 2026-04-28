<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class WorkflowAutoApprovalPreferenceController extends Controller
{
    public function update(Request $request, DynamicWorkflowService $workflowService)
    {
        $user = $request->user();
        abort_unless(
            $workflowService->userMayAutoApproveWorkflow('agenda', $user)
            || $workflowService->userMayAutoApproveWorkflow('monthly_activities', $user),
            403
        );

        $data = $request->validate([
            'auto_approve_workflow_steps' => ['nullable', 'boolean'],
        ]);

        $user->forceFill([
            'auto_approve_workflow_steps' => (bool) ($data['auto_approve_workflow_steps'] ?? false),
        ])->save();

        $workflowService->autoApprovePendingStepsForUser($user->fresh());

        return back()->with('status', __('app.workflow_auto_approval.updated'));
    }
}
