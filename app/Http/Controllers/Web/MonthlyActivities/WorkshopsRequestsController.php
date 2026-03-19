<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\WorkshopsRequest;
use Illuminate\Http\Request;

class WorkshopsRequestsController extends Controller
{
    public function index()
    {
        $requests = WorkshopsRequest::with('event')->latest()->paginate(20);

        return view('pages.monthly_activities.workshops.index', compact('requests'));
    }

    public function update(Request $request, WorkshopsRequest $workshopsRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,approved,rejected,completed'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $workshopsRequest->update($data);

        return back()->with('status', 'Workshop request updated.');
    }
}
