<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Http\Requests\Fleet\UnavailabilityRequest;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Support\Facades\DB;

/** Maintenance / leave windows (plan §7.4). Vehicle windows: vehicles.manage; driver leave: drivers.manage. */
class UnavailabilityController extends Controller
{
    public function store(UnavailabilityRequest $request)
    {
        $window = DB::transaction(function () use ($request) {
            $window = Unavailability::create($request->attributesForStorage() + ['created_by' => $request->user()->id]);
            MovementAudit::record('unavailability.created', $window, null, $window->getAttributes());

            return $window;
        });

        return redirect()->to($this->backTo($window))->with('success', __('movement.flash.saved'));
    }

    public function destroy(Unavailability $unavailability)
    {
        $this->authorize('delete', $unavailability);

        DB::transaction(function () use ($unavailability) {
            $old = $unavailability->getAttributes();
            $unavailability->delete();
            MovementAudit::record('unavailability.deleted', $unavailability, $old, null);
        });

        return redirect()->to($this->backTo($unavailability))->with('success', __('movement.flash.deleted'));
    }

    private function backTo(Unavailability $window): string
    {
        return $window->driver_id !== null
            ? route('movement.drivers.show', $window->driver_id)
            : route('movement.vehicles.show', $window->vehicle_id);
    }
}
