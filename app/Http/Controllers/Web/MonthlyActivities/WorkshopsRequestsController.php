<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\WorkshopsRequest;
use Illuminate\Http\Request;

class WorkshopsRequestsController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->input('status', '');
        $requiresWorkshops = $request->input('requires_workshops', '1');

        $requests = WorkshopsRequest::query()
            ->with(['event.branch', 'event.creator'])
            ->whereHas('event', function ($query) use ($requiresWorkshops) {
                if ($requiresWorkshops === '1') {
                    $query->where('requires_workshops', true);
                }
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            'completed' => 'تم إنشاء المطلوب',
        ];

        return view('pages.monthly_activities.workshops.index', compact('requests', 'statuses', 'status', 'requiresWorkshops'));
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
