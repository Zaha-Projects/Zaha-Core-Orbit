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
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ]);

        $maintenanceRequest->update($data);

        return redirect()
            ->route('role.maintenance.requests.index')
            ->with('status', __('app.roles.maintenance.requests.updated', ['request' => $maintenanceRequest->id]));
    }

    public function close(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'closed_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $maintenanceRequest->update([
            'closed_at' => $data['closed_at'] ?? now(),
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('role.maintenance.requests.index')
            ->with('status', __('app.roles.maintenance.requests.closed', ['request' => $maintenanceRequest->id]));
    }
}
