<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class TripsController extends Controller
{
    public function index()
    {
        $trips = Trip::with(['driver', 'vehicle'])->orderByDesc('trip_date')->get();
        $drivers = Driver::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('vehicle_no')->get();

        return view('roles.transport.trips.index', compact('trips', 'drivers', 'vehicles'));
    }

    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('vehicle_no')->get();

        return view('roles.transport.trips.create', compact('drivers', 'vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trip_date' => ['required', 'date'],
            'day_name' => ['nullable', 'string', 'max:50'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Trip::create([
            'trip_date' => $data['trip_date'],
            'day_name' => $data['day_name'] ?? null,
            'driver_id' => $data['driver_id'],
            'vehicle_id' => $data['vehicle_id'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.transport.trips.index')
            ->with('status', __('app.roles.transport.trips.created'));
    }

    public function edit(Trip $trip)
    {
        $trip->load(['segments', 'rounds']);
        $drivers = Driver::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('vehicle_no')->get();

        return view('roles.transport.trips.edit', compact('trip', 'drivers', 'vehicles'));
    }

    public function update(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'trip_date' => ['required', 'date'],
            'day_name' => ['nullable', 'string', 'max:50'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $trip->update([
            'trip_date' => $data['trip_date'],
            'day_name' => $data['day_name'] ?? null,
            'driver_id' => $data['driver_id'],
            'vehicle_id' => $data['vehicle_id'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('role.transport.trips.index')
            ->with('status', __('app.roles.transport.trips.updated', ['trip' => $trip->id]));
    }

    public function close(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $trip->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $trip->notes,
        ]);

        return redirect()
            ->route('role.transport.trips.index')
            ->with('status', __('app.roles.transport.trips.closed', ['trip' => $trip->id]));
    }
}
