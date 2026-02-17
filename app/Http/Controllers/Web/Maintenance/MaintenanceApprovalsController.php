<?php

namespace App\Http\Controllers\Web\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceApproval;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceApprovalsController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::with(['approvals', 'branch', 'center'])
            ->orderByDesc('logged_at')
            ->get();

        return view('pages.maintenance.approvals.index', compact('requests'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
        ]);

        MaintenanceApproval::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'step' => 'closure_review',
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $maintenanceRequest->update([
            'status' => $data['decision'] === 'approved' ? 'closed' : 'changes_requested',
            'closed_at' => $data['decision'] === 'approved' ? now() : null,
        ]);

        return redirect()
            ->route('role.maintenance.approvals.index')
            ->with('status', __('app.roles.maintenance.approvals.updated', ['request' => $maintenanceRequest->id]));
    }
}
