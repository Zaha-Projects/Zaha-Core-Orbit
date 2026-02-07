<?php

namespace App\Http\Controllers\Roles\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceAttachmentsController extends Controller
{
    public function store(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $data = $request->validate([
            'file_type' => ['required', 'string', 'max:50'],
            'file_path' => ['required', 'string', 'max:255'],
        ]);

        MaintenanceAttachment::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'file_type' => $data['file_type'],
            'file_path' => $data['file_path'],
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.maintenance.requests.edit', $maintenanceRequest)
            ->with('status', __('app.roles.maintenance.attachments.created'));
    }

    public function destroy(MaintenanceAttachment $maintenanceAttachment)
    {
        $requestId = $maintenanceAttachment->maintenance_request_id;
        $maintenanceAttachment->delete();

        return redirect()
            ->route('role.maintenance.requests.edit', $requestId)
            ->with('status', __('app.roles.maintenance.attachments.deleted'));
    }
}
