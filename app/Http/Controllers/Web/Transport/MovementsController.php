<?php

namespace App\Http\Controllers\Web\Transport;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MovementDay;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementsController extends Controller
{
    public function index(Request $request)
    {
        $query = MovementDay::query()->with(['driver', 'trips']);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->date('to_date'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($inner) use ($search) {
                $inner->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $movementDays = $query->orderByDesc('date')->paginate(20)->withQueryString();
        $drivers = Driver::orderBy('name')->get();

        return view('pages.transport.movements.index', compact('movementDays', 'drivers'));
    }

    public function create()
    {
        $drivers = Driver::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('vehicle_no')->get();

        return view('pages.transport.movements.create', compact('drivers', 'vehicles'));
    }

    public function store(Request $request)
    {
        $data = $this->validateMovement($request);

        DB::transaction(function () use ($data, $request) {
            $movementDay = MovementDay::create([
                'driver_id' => $data['driver_id'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['trips'] as $trip) {
                $movementDay->trips()->create($trip);
            }
        });

        return redirect()->route('role.transport.movements.index')->with('status', __('app.roles.transport.movements.created'));
    }

    public function show(MovementDay $movementDay)
    {
        $movementDay->load(['driver', 'trips.vehicle']);

        return view('pages.transport.movements.show', compact('movementDay'));
    }

    public function edit(MovementDay $movementDay)
    {
        $movementDay->load('trips');
        $drivers = Driver::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('vehicle_no')->get();

        return view('pages.transport.movements.edit', compact('movementDay', 'drivers', 'vehicles'));
    }

    public function update(Request $request, MovementDay $movementDay)
    {
        $data = $this->validateMovement($request, $movementDay->id);

        DB::transaction(function () use ($data, $movementDay) {
            $movementDay->update([
                'driver_id' => $data['driver_id'],
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $movementDay->trips()->delete();

            foreach ($data['trips'] as $trip) {
                $movementDay->trips()->create($trip);
            }
        });

        return redirect()->route('role.transport.movements.index')->with('status', __('app.roles.transport.movements.updated'));
    }

    public function destroy(MovementDay $movementDay)
    {
        $movementDay->delete();

        return redirect()->route('role.transport.movements.index')->with('status', __('app.roles.transport.movements.deleted'));
    }

    private function validateMovement(Request $request, ?int $movementDayId = null): array
    {
        $data = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'date' => ['required', 'date', 'unique:movement_days,date,'.$movementDayId.',id,driver_id,'.$request->input('driver_id')],
            'notes' => ['nullable', 'string'],
            'trips' => ['required', 'array', 'min:1'],
            'trips.*.vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'trips.*.destination' => ['required', 'string', 'max:255'],
            'trips.*.team' => ['nullable', 'string', 'max:255'],
            'trips.*.departure_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'trips.*.return_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ]);

        foreach ($data['trips'] as &$trip) {
            if (! empty($trip['departure_time'])) {
                $trip['departure_time'] = substr($trip['departure_time'], 0, 5);
            }

            if (! empty($trip['return_time'])) {
                $trip['return_time'] = substr($trip['return_time'], 0, 5);
            }
        }

        return $data;
    }
}
