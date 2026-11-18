<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Http\Requests\Fleet\VehicleRequest;
use App\Modules\Movement\Http\Requests\Fleet\VehicleStatusRequest;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Services\TripSchedulingService;
use App\Modules\Movement\Support\MovementAudit;
use App\Modules\Movement\Support\MovementTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Fleet management (plan §7.2, §13.1, screen 2). Manager-only (movement.vehicles.manage, D7). */
class VehicleController extends Controller
{
    /** Statuses that take a vehicle out of service; refused while it is on a non-terminal trip (Revision 6). */
    private const WITHDRAWN_STATUSES = [Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_INACTIVE];

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
        try {
            MovementTransaction::run(function () use ($request, $vehicle) {
                $vehicle = MovementTransaction::lockVehicles([$vehicle->id])->first();
                $before = $vehicle->getAttributes();
                // §13.6 (Revision 4, 6): capacity cannot drop below the seats offered on a non-terminal trip.
                $capacity = (int) $request->validated()['passenger_capacity'];
                if ($vehicle->passenger_capacity === null || $capacity < $vehicle->passenger_capacity) {
                    app(TripSchedulingService::class)->guardVehicleCapacity($vehicle, $capacity);
                }
                $vehicle->update($request->validated());
                [$old, $new] = MovementAudit::diff($before, $vehicle);
                if ($new) {
                    MovementAudit::record('vehicle.updated', $vehicle, $old, $new);
                }
            });
        } catch (IllegalMovementTransition $e) {
            return back()->withErrors(['passenger_capacity' => $e->getMessage()])->withInput();
        }

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.saved'));
    }

    public function changeStatus(VehicleStatusRequest $request, Vehicle $vehicle)
    {
        try {
            MovementTransaction::run(function () use ($request, $vehicle) {
                $vehicle = MovementTransaction::lockVehicles([$vehicle->id])->first();
                // §13.6 (Revision 6): not withdrawn from service while on a draft or scheduled trip.
                if (in_array($request->validated()['operational_status'], self::WITHDRAWN_STATUSES, true)) {
                    app(TripSchedulingService::class)->guardResourceWithdrawal('vehicle', $vehicle->id);
                }
                $old = $vehicle->only(['operational_status', 'status_reason']);
                $vehicle->update($request->validated() + ['status_changed_at' => now(), 'status_changed_by' => $request->user()->id]);
                MovementAudit::record('vehicle.status_changed', $vehicle, $old, $vehicle->only(['operational_status', 'status_reason']));
            });
        } catch (IllegalMovementTransition $e) {
            return back()->withErrors(['operational_status' => $e->getMessage()])->withInput();
        }

        return redirect()->route('movement.vehicles.show', $vehicle)->with('success', __('movement.flash.status_changed'));
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        try {
            MovementTransaction::run(function () use ($vehicle) {
                $vehicle = MovementTransaction::lockVehicles([$vehicle->id])->first();
                app(TripSchedulingService::class)->guardResourceWithdrawal('vehicle', $vehicle->id); // §13.6, Revision 6
                $vehicle->delete();
                MovementAudit::record('vehicle.deleted', $vehicle, null, ['deleted_at' => (string) $vehicle->deleted_at]);
            });
        } catch (IllegalMovementTransition $e) {
            return back()->withErrors(['vehicle' => $e->getMessage()]);
        }

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
