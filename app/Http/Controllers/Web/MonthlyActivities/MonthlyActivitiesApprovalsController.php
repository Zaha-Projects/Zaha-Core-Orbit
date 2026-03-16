<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\WorkflowActionLog;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class MonthlyActivitiesApprovalsController extends Controller
{
    protected array $stepToStatusField = [
        'relations_officer_review' => 'relations_officer_approval_status',
        'relations_manager_review' => 'relations_manager_approval_status',
        'programs_officer_review' => 'programs_officer_approval_status',
        'programs_manager_review' => 'programs_manager_approval_status',
        'executive_review' => 'executive_approval_status',
    ];

    protected function resolveStepAndField($user): array
    {
        if ($user->hasRole('relations_officer')) {
            return ['relations_officer_review', 'relations_officer_approval_status'];
        }

        if ($user->hasRole('relations_manager')) {
            return ['relations_manager_review', 'relations_manager_approval_status'];
        }

        if ($user->hasRole('programs_officer')) {
            return ['programs_officer_review', 'programs_officer_approval_status'];
        }

        if ($user->hasRole('programs_manager')) {
            return ['programs_manager_review', 'programs_manager_approval_status'];
        }

        if ($user->hasRole('executive_manager')) {
            return ['executive_review', 'executive_approval_status'];
        }

        abort(403);
    }

    protected function assertStepOrder(MonthlyActivity $monthlyActivity, string $step): void
    {
        $requiredApprovedByStep = [
            'relations_officer_review' => null,
            'relations_manager_review' => 'relations_officer_approval_status',
            'programs_officer_review' => 'relations_manager_approval_status',
            'programs_manager_review' => 'programs_officer_approval_status',
            'executive_review' => 'programs_manager_approval_status',
        ];

        $requiredField = $requiredApprovedByStep[$step] ?? null;
        if ($requiredField && $monthlyActivity->{$requiredField} !== 'approved') {
            abort(422, __('app.roles.programs.monthly_activities.approvals.errors.prerequisite_missing'));
        }
    }

    public function index()
    {
        $activities = MonthlyActivity::with(['approvals', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $stepLabels = [
            'relations_officer_review' => __('Relations officer'),
            'relations_manager_review' => __('Relations manager'),
            'programs_officer_review' => __('Programs officer'),
            'programs_manager_review' => __('Programs manager'),
            'executive_review' => __('Executive manager'),
        ];

        return view('pages.monthly_activities.approvals.index', compact('activities', 'stepLabels'));
    }

    public function update(Request $request, NotificationService $notifications, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
            'is_edit_request_implemented' => ['nullable', 'boolean'],
        ]);

        [$step, $statusField] = $this->resolveStepAndField($request->user());
        $this->assertStepOrder($monthlyActivity, $step);

        MonthlyActivityApproval::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => $step,
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'is_edit_request_implemented' => (bool) ($data['is_edit_request_implemented'] ?? false),
            'implemented_at' => !empty($data['is_edit_request_implemented']) ? now() : null,
        ]);

        $updates = [
            $statusField => $data['decision'],
            'status' => $data['decision'] === 'approved' ? 'in_review' : 'changes_requested',
        ];

        if ($step === 'executive_review') {
            $updates['status'] = $data['decision'] === 'approved' ? 'approved' : 'changes_requested';
        }

        if ($data['decision'] === 'changes_requested') {
            $workflowSteps = array_keys($this->stepToStatusField);
            $currentStepIndex = array_search($step, $workflowSteps, true);

            if ($currentStepIndex !== false) {
                foreach (array_slice($workflowSteps, $currentStepIndex + 1) as $downstreamStep) {
                    $updates[$this->stepToStatusField[$downstreamStep]] = 'pending';
                }
            }
        }

        $monthlyActivity->update($updates);


        $notifications->notifyUsers(collect([$monthlyActivity->creator])->filter(), 'approval_decision', 'Approval update', 'Decision: '. $data['decision'], route('role.programs.approvals.index'));

        WorkflowActionLog::create([
            'module' => 'monthly_activity',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $request->user()->id,
            'meta' => ['step' => $step],
            'performed_at' => now(),
        ]);

        return redirect()
            ->route('role.programs.approvals.index')
            ->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }
}
