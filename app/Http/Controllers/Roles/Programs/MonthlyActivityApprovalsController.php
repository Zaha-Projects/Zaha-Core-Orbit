<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use Illuminate\Http\Request;

class MonthlyActivityApprovalsController extends Controller
{
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
            abort(422, 'لا يمكن تنفيذ هذه المرحلة قبل اكتمال المرحلة السابقة.');
        }
    }

    public function index()
    {
        $activities = MonthlyActivity::with(['approvals', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        return view('roles.programs.monthly_activities.approvals', compact('activities'));
    }

    public function update(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
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
        ]);

        $updates = [
            $statusField => $data['decision'],
            'status' => $data['decision'] === 'approved' ? 'in_review' : 'changes_requested',
        ];

        if ($step === 'executive_review') {
            $updates['status'] = $data['decision'] === 'approved' ? 'approved' : 'changes_requested';
        }

        if ($data['decision'] === 'changes_requested') {
            if ($step !== 'relations_officer_review') {
                $updates['relations_manager_approval_status'] = 'pending';
                $updates['programs_officer_approval_status'] = 'pending';
                $updates['programs_manager_approval_status'] = 'pending';
                $updates['executive_approval_status'] = 'pending';
            }
        }

        $monthlyActivity->update($updates);

        return redirect()
            ->route('role.programs.approvals.index')
            ->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }
}
