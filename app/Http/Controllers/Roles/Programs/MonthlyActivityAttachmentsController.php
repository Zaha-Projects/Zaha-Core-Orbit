<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityAttachment;
use Illuminate\Http\Request;

class MonthlyActivityAttachmentsController extends Controller
{
    public function store(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'file_type' => ['required', 'string', 'max:50'],
            'file_path' => ['required', 'string', 'max:255'],
        ]);

        MonthlyActivityAttachment::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'file_type' => $data['file_type'],
            'file_path' => $data['file_path'],
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.programs.activities.edit', $monthlyActivity)
            ->with('status', __('app.roles.programs.monthly_activities.attachments.created'));
    }

    public function destroy(MonthlyActivityAttachment $monthlyActivityAttachment)
    {
        $activityId = $monthlyActivityAttachment->monthly_activity_id;
        $monthlyActivityAttachment->delete();

        return redirect()
            ->route('role.programs.activities.edit', $activityId)
            ->with('status', __('app.roles.programs.monthly_activities.attachments.deleted'));
    }
}
