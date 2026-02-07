<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use Illuminate\Http\Request;

class MonthlyActivityApprovalsController extends Controller
{
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

        MonthlyActivityApproval::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => 'programs_review',
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $monthlyActivity->update([
            'status' => $data['decision'] === 'approved' ? 'approved' : 'changes_requested',
        ]);

        return redirect()
            ->route('role.programs.approvals.index')
            ->with('status', __('app.roles.programs.monthly_activities.approvals.updated', ['activity' => $monthlyActivity->title]));
    }
}
