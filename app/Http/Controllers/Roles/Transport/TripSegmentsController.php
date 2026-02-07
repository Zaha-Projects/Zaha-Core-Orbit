<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripSegment;
use Illuminate\Http\Request;

class TripSegmentsController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'segment_no' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'team_companion' => ['nullable', 'string', 'max:255'],
            'depart_time' => ['nullable', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        TripSegment::create([
            'trip_id' => $trip->id,
            'segment_no' => $data['segment_no'],
            'location' => $data['location'],
            'team_companion' => $data['team_companion'] ?? null,
            'depart_time' => $data['depart_time'] ?? null,
            'return_time' => $data['return_time'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('role.transport.trips.edit', $trip)
            ->with('status', __('app.roles.transport.segments.created'));
    }

    public function update(Request $request, TripSegment $tripSegment)
    {
        $data = $request->validate([
            'segment_no' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'team_companion' => ['nullable', 'string', 'max:255'],
            'depart_time' => ['nullable', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $tripSegment->update($data);

        return redirect()
            ->route('role.transport.trips.edit', $tripSegment->trip_id)
            ->with('status', __('app.roles.transport.segments.updated'));
    }

    public function destroy(TripSegment $tripSegment)
    {
        $tripId = $tripSegment->trip_id;
        $tripSegment->delete();

        return redirect()
            ->route('role.transport.trips.edit', $tripId)
            ->with('status', __('app.roles.transport.segments.deleted'));
    }
}
