<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\CommunicationsRequest;
use Illuminate\Http\Request;

class CommunicationsRequestsController extends Controller
{
    public function index()
    {
        $requests = CommunicationsRequest::with('event')->latest()->paginate(20);

        return view('pages.monthly_activities.communications.index', compact('requests'));
    }

    public function update(Request $request, CommunicationsRequest $communicationsRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,approved,rejected,completed'],
            'notes' => ['nullable', 'string'],
            'media_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = $communicationsRequest->media_files ?? [];
        foreach ($request->file('media_files', []) as $file) {
            $files[] = $file->store("events/{$communicationsRequest->event_id}", 'public');
        }

        $communicationsRequest->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'media_files' => $files,
        ]);

        return back()->with('status', 'Communications request updated.');
    }
}
