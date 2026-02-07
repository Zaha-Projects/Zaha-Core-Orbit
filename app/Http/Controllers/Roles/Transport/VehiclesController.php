<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehiclesController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('branch')->orderBy('vehicle_no')->get();
        $branches = Branch::orderBy('name')->get();

        return view('roles.transport.vehicles.index', compact('vehicles', 'branches'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();

        return view('roles.transport.vehicles.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_no' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        Vehicle::create($data);

        return redirect()
            ->route('role.transport.vehicles.index')
            ->with('status', __('app.roles.transport.vehicles.created'));
    }

    public function edit(Vehicle $vehicle)
    {
        $branches = Branch::orderBy('name')->get();

        return view('roles.transport.vehicles.edit', compact('vehicle', 'branches'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'plate_no' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $vehicle->update($data);

        return redirect()
            ->route('role.transport.vehicles.index')
            ->with('status', __('app.roles.transport.vehicles.updated', ['vehicle' => $vehicle->vehicle_no ?? $vehicle->plate_no]));
    }
}
