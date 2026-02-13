<?php

namespace App\Http\Controllers\Roles\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceRequestsController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::with(['branch', 'center', 'creator'])->orderByDesc('logged_at')->get();
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.maintenance.requests.index', compact('requests', 'branches', 'centers'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.maintenance.requests.create', compact('branches', 'centers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'logged_at' => ['required', 'date'],
            'type' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'branch_head_status' => ['nullable', 'string', 'max:50'],
            'branch_head_note' => ['nullable', 'string'],
            'maintenance_track_status' => ['nullable', 'string', 'max:50'],
            'maintenance_track_note' => ['nullable', 'string'],
            'it_track_status' => ['nullable', 'string', 'max:50'],
            'it_track_note' => ['nullable', 'string'],
            'support_resources' => ['nullable', 'string'],
            'support_party' => ['nullable', 'string', 'max:255'],
            'root_cause_branch' => ['nullable', 'string'],
            'root_cause_maintenance' => ['nullable', 'string'],
            'root_cause_it' => ['nullable', 'string'],
            'closure_summary' => ['nullable', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ]);

        MaintenanceRequest::create([
            'logged_at' => $data['logged_at'],
            'type' => $data['type'],
            'category' => $data['category'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'branch_head_status' => $data['branch_head_status'] ?? null,
            'branch_head_note' => $data['branch_head_note'] ?? null,
            'branch_head_updated_at' => isset($data['branch_head_status']) ? now() : null,
            'maintenance_track_status' => $data['maintenance_track_status'] ?? null,
            'maintenance_track_note' => $data['maintenance_track_note'] ?? null,
            'maintenance_track_updated_at' => isset($data['maintenance_track_status']) ? now() : null,
            'it_track_status' => $data['it_track_status'] ?? null,
            'it_track_note' => $data['it_track_note'] ?? null,
            'it_track_updated_at' => isset($data['it_track_status']) ? now() : null,
            'support_resources' => $data['support_resources'] ?? null,
            'support_party' => $data['support_party'] ?? null,
            'root_cause_branch' => $data['root_cause_branch'] ?? null,
            'root_cause_maintenance' => $data['root_cause_maintenance'] ?? null,
            'root_cause_it' => $data['root_cause_it'] ?? null,
            'closure_summary' => $data['closure_summary'] ?? null,
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.maintenance.requests.index')
            ->with('status', __('app.roles.maintenance.requests.created'));
    }

    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->load(['workDetails', 'attachments', 'approvals']);
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.maintenance.requests.edit', compact('maintenanceRequest', 'branches', 'centers'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'logged_at' => ['required', 'date'],
            'type' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'branch_head_status' => ['nullable', 'string', 'max:50'],
            'branch_head_note' => ['nullable', 'string'],
            'maintenance_track_status' => ['nullable', 'string', 'max:50'],
            'maintenance_track_note' => ['nullable', 'string'],
            'it_track_status' => ['nullable', 'string', 'max:50'],
            'it_track_note' => ['nullable', 'string'],
            'support_resources' => ['nullable', 'string'],
            'support_party' => ['nullable', 'string', 'max:255'],
            'root_cause_branch' => ['nullable', 'string'],
            'root_cause_maintenance' => ['nullable', 'string'],
            'root_cause_it' => ['nullable', 'string'],
            'closure_summary' => ['nullable', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ]);

        $maintenanceRequest->update(array_merge($data, [
            'branch_head_updated_at' => isset($data['branch_head_status']) ? now() : $maintenanceRequest->branch_head_updated_at,
            'maintenance_track_updated_at' => isset($data['maintenance_track_status']) ? now() : $maintenanceRequest->maintenance_track_updated_at,
            'it_track_updated_at' => isset($data['it_track_status']) ? now() : $maintenanceRequest->it_track_updated_at,
        ]));

        return redirect()
            ->route('role.maintenance.requests.index')
            ->with('status', __('app.roles.maintenance.requests.updated', ['request' => $maintenanceRequest->id]));
    }

    public function close(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'closed_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'closure_summary' => ['nullable', 'string'],
        ]);

        $maintenanceRequest->approvals()->create([
            'step' => 'closure',
            'decision' => $data['status'],
            'comment' => $data['closure_summary'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $maintenanceRequest->update([
            'closed_at' => $data['closed_at'] ?? now(),
            'status' => $data['status'],
            'closure_summary' => $data['closure_summary'] ?? $maintenanceRequest->closure_summary,
        ]);

        return redirect()
            ->route('role.maintenance.requests.index')
            ->with('status', __('app.roles.maintenance.requests.closed', ['request' => $maintenanceRequest->id]));
    }
}
