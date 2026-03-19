<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MonthlyActivityAttachmentsController extends Controller
{
    public function store(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'file_type' => ['required', 'in:image,document,report,other'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xlsx,xls', 'max:10240'],
        ]);

        $path = $request->file('file')->store("events/{$monthlyActivity->id}", 'public');

        MonthlyActivityAttachment::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'file_type' => $data['file_type'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post'])
            ->with('status', __('app.roles.programs.monthly_activities.attachments.created'));
    }

    public function destroy(MonthlyActivityAttachment $monthlyActivityAttachment)
    {
        $activityId = $monthlyActivityAttachment->monthly_activity_id;
        if ($monthlyActivityAttachment->file_path) {
            Storage::disk('public')->delete($monthlyActivityAttachment->file_path);
        }
        $monthlyActivityAttachment->delete();

        return redirect()
            ->route('role.relations.activities.edit', ['monthlyActivity' => $activityId, 'mode' => 'post'])
            ->with('status', __('app.roles.programs.monthly_activities.attachments.deleted'));
    }
}
