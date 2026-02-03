<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripRound;
use Illuminate\Http\Request;

class TripRoundsController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'round_no' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        TripRound::create([
            'trip_id' => $trip->id,
            'round_no' => $data['round_no'],
            'location' => $data['location'],
            'team' => $data['team'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('role.transport.trips.edit', $trip)
            ->with('status', __('app.roles.transport.rounds.created'));
    }

    public function update(Request $request, TripRound $tripRound)
    {
        $data = $request->validate([
            'round_no' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:255'],
            'team' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $tripRound->update($data);

        return redirect()
            ->route('role.transport.trips.edit', $tripRound->trip_id)
            ->with('status', __('app.roles.transport.rounds.updated'));
    }

    public function destroy(TripRound $tripRound)
    {
        $tripId = $tripRound->trip_id;
        $tripRound->delete();

        return redirect()
            ->route('role.transport.trips.edit', $tripId)
            ->with('status', __('app.roles.transport.rounds.deleted'));
    }
}
