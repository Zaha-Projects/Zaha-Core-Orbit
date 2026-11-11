<?php

namespace App\Modules\Movement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;

/**
 * Public fleet overview (plan §5.2, §13.2, Revision 4 item 13): every authenticated user sees
 * aggregates only — per-type counts, capacity distribution and the active driver count. Nothing per
 * vehicle and no names (D7). Inactive vehicles and vehicles without a type are not counted.
 */
class MovementDashboardController extends Controller
{
    public function fleet()
    {
        $this->authorize('viewOverview', Vehicle::class);

        $vehicles = Vehicle::query()
            ->whereNotNull('vehicle_type_id')
            ->where('operational_status', '!=', Vehicle::STATUS_INACTIVE)
            ->get(['vehicle_type_id', 'passenger_capacity', 'operational_status'])
            ->groupBy('vehicle_type_id');

        $types = VehicleType::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (VehicleType $type) use ($vehicles) {
                $group = $vehicles->get($type->id, collect());

                return [
                    'name' => $type->display_name,
                    'size_class' => $type->size_class,
                    'total' => $group->count(),
                    'operational' => $group->where('operational_status', Vehicle::STATUS_OPERATIONAL)->count(),
                    'unavailable' => $group->where('operational_status', '!=', Vehicle::STATUS_OPERATIONAL)->count(),
                    'capacities' => $group->whereNotNull('passenger_capacity')->countBy('passenger_capacity')->sortKeys()->all(),
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->values();

        return view('pages.movement.fleet.overview', [
            'types' => $types,
            'activeDrivers' => Driver::query()->where('employment_status', Driver::STATUS_ACTIVE)->count(),
        ]);
    }
}
