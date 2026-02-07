<?php

namespace App\Http\Controllers\Roles\Transport;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriversController extends Controller
{
    public function index()
    {
        $drivers = Driver::orderBy('name')->get();

        return view('roles.transport.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('roles.transport.drivers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Driver::create($data);

        return redirect()
            ->route('role.transport.drivers.index')
            ->with('status', __('app.roles.transport.drivers.created'));
    }

    public function edit(Driver $driver)
    {
        return view('roles.transport.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $driver->update($data);

        return redirect()
            ->route('role.transport.drivers.index')
            ->with('status', __('app.roles.transport.drivers.updated', ['driver' => $driver->name]));
    }
}
