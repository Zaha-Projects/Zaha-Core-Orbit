<?php

namespace App\Http\Controllers\Roles\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceWorkDetail;
use Illuminate\Http\Request;

class MaintenanceWorkDetailsController extends Controller
{
    public function store(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'start_from' => ['nullable', 'date'],
            'end_to' => ['nullable', 'date'],
            'team_desc' => ['nullable', 'string'],
            'resources_type' => ['nullable', 'string', 'max:255'],
            'support_party' => ['nullable', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'root_cause_analysis' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        MaintenanceWorkDetail::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'start_from' => $data['start_from'] ?? null,
            'end_to' => $data['end_to'] ?? null,
            'team_desc' => $data['team_desc'] ?? null,
            'resources_type' => $data['resources_type'] ?? null,
            'support_party' => $data['support_party'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'root_cause_analysis' => $data['root_cause_analysis'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.maintenance.requests.edit', $maintenanceRequest)
            ->with('status', __('app.roles.maintenance.work_details.created'));
    }

    public function update(Request $request, MaintenanceWorkDetail $maintenanceWorkDetail)
    {
        $data = $request->validate([
            'start_from' => ['nullable', 'date'],
            'end_to' => ['nullable', 'date'],
            'team_desc' => ['nullable', 'string'],
            'resources_type' => ['nullable', 'string', 'max:255'],
            'support_party' => ['nullable', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'root_cause_analysis' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $maintenanceWorkDetail->update([
            'start_from' => $data['start_from'] ?? null,
            'end_to' => $data['end_to'] ?? null,
            'team_desc' => $data['team_desc'] ?? null,
            'resources_type' => $data['resources_type'] ?? null,
            'support_party' => $data['support_party'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'root_cause_analysis' => $data['root_cause_analysis'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.maintenance.requests.edit', $maintenanceWorkDetail->maintenance_request_id)
            ->with('status', __('app.roles.maintenance.work_details.updated'));
    }
}
