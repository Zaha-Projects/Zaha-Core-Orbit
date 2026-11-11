<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Http\Requests\Fleet\VehicleRequest;
use App\Modules\Movement\Http\Requests\Fleet\VehicleStatusRequest;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Fleet management (plan §7.2, §13.1, screen 2). Manager-only (movement.vehicles.manage, D7). */
class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Vehicle::STATUSES)],
            'vehicle_type_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'trashed' => ['nullable', 'boolean'],
        ]);

        $vehicles = Vehicle::query()
            ->with(['vehicleType', 'homeLocation'])
            ->when($filters['trashed'] ?? false, fn ($q) => $q->onlyTrashed())
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('operational_status', $status))
            ->when($filters['vehicle_type_id'] ?? null, fn ($q, $type) => $q->where('vehicle_type_id', $type))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($w) => $w->where('fleet_number', 'like', "%{$search}%")->orWhere('plate_number', 'like', "%{$search}%")))
            ->orderBy('fleet_number')
            ->paginate(25)
            ->withQueryString();

        return view('pages.movement.fleet.vehicles.index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'types' => VehicleType::query()->orderBy('sort_order')->get(),
            'needsVerification' => Vehicle::query()->where('operational_status', Vehicle::STATUS_PENDING_VERIFICATION)->orderBy('fleet_number')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Vehicle::class);

        return view('pages.movement.fleet.vehicles.form', $this->formOptions(new Vehicle()));
    }

    public function store(VehicleRequest $request)
    {
        $vehicle = DB::transaction(function () use ($request) {
            $vehicle = Vehicle::create($request->validated() + ['status_changed_at' => now(), 'status_changed_by' => $request->user()->id]);
            MovementAudit::record('vehicle.created', $vehicle, null, $vehicle->getAttributes());

            return $vehicle;
        });

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.saved'));
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        return view('pages.movement.fleet.vehicles.show', [
            'vehicle' => $vehicle->load(['vehicleType', 'homeLocation', 'statusChanger']),
            'windows' => $vehicle->unavailabilities()->orderByDesc('starts_at')->limit(50)->get(),
            'statuses' => VehicleRequest::manualStatuses(),
            'windowTypes' => Unavailability::TYPES,
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);

        return view('pages.movement.fleet.vehicles.form', $this->formOptions($vehicle));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle)
    {
        DB::transaction(function () use ($request, $vehicle) {
            $before = $vehicle->getAttributes();
            // Phase 3: capacity edits are unrestricted; the §13.6 trip rules arrive in Phase 5 (Revision 4).
            $vehicle->update($request->validated());
            [$old, $new] = MovementAudit::diff($before, $vehicle);
            if ($new) {
                MovementAudit::record('vehicle.updated', $vehicle, $old, $new);
            }
        });

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.saved'));
    }

    public function changeStatus(VehicleStatusRequest $request, Vehicle $vehicle)
    {
        DB::transaction(function () use ($request, $vehicle) {
            $old = $vehicle->only(['operational_status', 'status_reason']);
            $vehicle->update($request->validated() + ['status_changed_at' => now(), 'status_changed_by' => $request->user()->id]);
            MovementAudit::record('vehicle.status_changed', $vehicle, $old, $vehicle->only(['operational_status', 'status_reason']));
        });

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.status_changed'));
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        DB::transaction(function () use ($vehicle) {
            $vehicle->delete();
            MovementAudit::record('vehicle.deleted', $vehicle, null, ['deleted_at' => (string) $vehicle->deleted_at]);
        });

        return redirect()->route('movement.vehicles.index')->with('success', __('movement.flash.deleted'));
    }

    public function restore(Vehicle $vehicle)
    {
        $this->authorize('restore', $vehicle);

        DB::transaction(function () use ($vehicle) {
            $vehicle->restore();
            MovementAudit::record('vehicle.restored', $vehicle);
        });

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.restored'));
    }

    private function formOptions(Vehicle $vehicle): array
    {
        return [
            'vehicle' => $vehicle,
            'types' => VehicleType::query()->where('is_active', true)->orWhere('id', $vehicle->vehicle_type_id)->orderBy('sort_order')->get(),
            'locations' => Location::query()->where('is_active', true)->orWhere('id', $vehicle->home_location_id)->orderBy('name_ar')->get(),
            'statuses' => VehicleRequest::manualStatuses(),
        ];
    }
}
