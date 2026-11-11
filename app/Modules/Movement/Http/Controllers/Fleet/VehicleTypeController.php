<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Http\Requests\Fleet\VehicleTypeRequest;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Support\Facades\DB;

/** Vehicle types (plan §7.1, screen 2). Thin: policy → Form Request → write + audit → redirect. */
class VehicleTypeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', VehicleType::class);

        return view('pages.movement.fleet.vehicle-types.index', [
            'types' => VehicleType::withTrashed()->withCount('vehicles')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(VehicleTypeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['sort_order'] ??= 0;
            $type = VehicleType::create($data);
            MovementAudit::record('vehicle_type.created', $type, null, $type->getAttributes());
        });

        return redirect()->route('movement.vehicle-types.index')->with('success', __('movement.flash.saved'));
    }

    public function edit(VehicleType $vehicleType)
    {
        $this->authorize('update', $vehicleType);

        return view('pages.movement.fleet.vehicle-types.edit', ['type' => $vehicleType]);
    }

    public function update(VehicleTypeRequest $request, VehicleType $vehicleType)
    {
        DB::transaction(function () use ($request, $vehicleType) {
            $before = $vehicleType->getAttributes();
            $data = $request->validated();
            $data['sort_order'] ??= $vehicleType->sort_order;
            $vehicleType->update($data);
            [$old, $new] = MovementAudit::diff($before, $vehicleType);
            if ($new) {
                MovementAudit::record('vehicle_type.updated', $vehicleType, $old, $new);
            }
        });

        return redirect()->route('movement.vehicle-types.index')->with('success', __('movement.flash.saved'));
    }

    public function destroy(VehicleType $vehicleType)
    {
        $this->authorize('delete', $vehicleType);

        DB::transaction(function () use ($vehicleType) {
            $vehicleType->delete();
            MovementAudit::record('vehicle_type.deleted', $vehicleType, null, ['deleted_at' => (string) $vehicleType->deleted_at]);
        });

        return redirect()->route('movement.vehicle-types.index')->with('success', __('movement.flash.deleted'));
    }

    public function restore(VehicleType $vehicleType)
    {
        $this->authorize('restore', $vehicleType);

        DB::transaction(function () use ($vehicleType) {
            $vehicleType->restore();
            MovementAudit::record('vehicle_type.restored', $vehicleType);
        });

        return redirect()->route('movement.vehicle-types.index')->with('success', __('movement.flash.restored'));
    }
}
