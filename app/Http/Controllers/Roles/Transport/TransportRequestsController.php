<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\TransportRequest;
use Illuminate\Http\Request;

class TransportRequestsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isTransportOfficer = $user->hasRole('transport_officer');

        $requestsQuery = TransportRequest::with([
            'requester',
            'branch',
            'driver',
            'trips.vehicle',
            'actions.actor',
            'feedback',
        ])->orderByDesc('request_date');

        if (! $isTransportOfficer) {
            $requestsQuery->where('requester_id', $user->id);
        }

        $requests = $requestsQuery->get();
        $drivers = Driver::orderBy('name')->get();

        return view('roles.transport.requests.index', compact('requests', 'drivers', 'isTransportOfficer'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'request_date' => ['required', 'date'],
            'day_name' => ['nullable', 'string', 'max:30'],
            'general_notes' => ['nullable', 'string'],
            'destination' => ['required', 'string', 'max:255'],
            'accompanying_team' => ['nullable', 'string', 'max:255'],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
        ]);

        $transportRequest = TransportRequest::create([
            'requester_id' => $request->user()->id,
            'requester_branch_id' => $request->user()->branch_id,
            'request_date' => $data['request_date'],
            'day_name' => $data['day_name'] ?? null,
            'status' => 'pending',
            'general_notes' => $data['general_notes'] ?? null,
        ]);

        $transportRequest->trips()->create([
            'trip_no' => 1,
            'destination' => $data['destination'],
            'accompanying_team' => $data['accompanying_team'] ?? null,
            'departure_time' => $data['departure_time'] ?? null,
            'return_time' => $data['return_time'] ?? null,
        ]);

        $transportRequest->actions()->create([
            'action_type' => 'created',
            'action_by' => $request->user()->id,
            'action_at' => now(),
            'comment' => 'تم إنشاء الطلب من مقدم الطلب.',
        ]);

        return redirect()->route('role.transport.requests.index')->with('status', 'تم إرسال طلب الحركة بنجاح.');
    }

    public function process(Request $request, TransportRequest $transportRequest)
    {
        abort_unless($request->user()->hasRole('transport_officer'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:in_review,approved,rejected,closed'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'movement_officer_notes' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'destination' => ['nullable', 'string', 'max:255'],
            'accompanying_team' => ['nullable', 'string', 'max:255'],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
        ]);

        $transportRequest->update([
            'status' => $data['status'],
            'driver_id' => $data['driver_id'] ?? null,
            'movement_officer_notes' => $data['movement_officer_notes'] ?? null,
        ]);

        $trip = $transportRequest->trips()->first();
        if ($trip) {
            $trip->update([
                'destination' => $data['destination'] ?? $trip->destination,
                'accompanying_team' => $data['accompanying_team'] ?? $trip->accompanying_team,
                'departure_time' => $data['departure_time'] ?? $trip->departure_time,
                'return_time' => $data['return_time'] ?? $trip->return_time,
            ]);
        }

        $transportRequest->actions()->create([
            'action_type' => $data['status'],
            'action_by' => $request->user()->id,
            'action_at' => now(),
            'comment' => $data['comment'] ?? null,
        ]);

        return redirect()->route('role.transport.requests.index')->with('status', 'تم تحديث الطلب بنجاح.');
    }

    public function feedback(Request $request, TransportRequest $transportRequest)
    {
        abort_unless((int) $transportRequest->requester_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'punctuality_score' => ['nullable', 'integer', 'between:1,5'],
            'cleanliness_score' => ['nullable', 'integer', 'between:1,5'],
            'driver_behavior_score' => ['nullable', 'integer', 'between:1,5'],
            'overall_score' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ]);

        $transportRequest->feedback()->updateOrCreate(
            ['transport_request_id' => $transportRequest->id],
            array_merge($data, ['submitted_by' => $request->user()->id])
        );

        $transportRequest->actions()->create([
            'action_type' => 'feedback_submitted',
            'action_by' => $request->user()->id,
            'action_at' => now(),
            'comment' => $data['comment'] ?? 'تم إرسال تقييم الطلب.',
        ]);

        return redirect()->route('role.transport.requests.index')->with('status', 'تم حفظ تقييم الطلب.');
    }
}
