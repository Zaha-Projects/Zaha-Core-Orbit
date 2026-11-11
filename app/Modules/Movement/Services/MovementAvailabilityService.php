<?php

namespace App\Modules\Movement\Services;

use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementTime;
use Carbon\CarbonInterface;

/**
 * Availability figures (plan §13.4). Phase 4 provides summary() only (Revision 5): it uses
 * operational_status and movement_unavailabilities; trip windows and paired-trip reservations
 * are added in Phase 5. Output is aggregate only — never per-vehicle or per-driver data (D7).
 */
class MovementAvailabilityService
{
    /**
     * Public figures for the window [from, until). Intervals overlap only when start < until AND end > from,
     * so a window that ends exactly when another starts is not an overlap.
     *
     * @return array{window: array{from: string, until: string}, types: array<int, array>, drivers: array{active: int, free: int}}
     */
    public function summary(CarbonInterface $from, CarbonInterface $until, ?int $minCapacity = null): array
    {
        $blockedVehicles = $this->blocked('vehicle_id', $from, $until);

        $vehicles = Vehicle::query()
            ->whereNotNull('vehicle_type_id')
            ->where('operational_status', '!=', Vehicle::STATUS_INACTIVE)
            ->get(['id', 'vehicle_type_id', 'passenger_capacity', 'operational_status'])
            ->groupBy('vehicle_type_id');

        $types = VehicleType::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (VehicleType $type) use ($vehicles, $blockedVehicles, $minCapacity) {
                $group = $vehicles->get($type->id, collect());
                $free = $group->filter(fn (Vehicle $v) => $v->operational_status === Vehicle::STATUS_OPERATIONAL && ! isset($blockedVehicles[$v->id]));

                return [
                    'type' => $type->display_name,
                    'size_class' => $type->size_class,
                    'total' => $group->count(),
                    'free' => $free->count(),
                    'unavailable' => $group->count() - $free->count(),
                    'free_with_capacity' => $minCapacity === null ? $free->count() : $free->filter(fn (Vehicle $v) => (int) $v->passenger_capacity >= $minCapacity)->count(),
                    'max_free_capacity' => (int) $free->max('passenger_capacity'),
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->values()
            ->all();

        // §13.2: active, licence not expired by the end of the window, no overlapping leave.
        $blockedDrivers = $this->blocked('driver_id', $from, $until);
        $lastDay = MovementTime::toLocal($until)->format('Y-m-d');
        $active = Driver::query()->where('employment_status', Driver::STATUS_ACTIVE)->get(['id', 'licence_expires_on']);
        $free = $active->filter(fn (Driver $d) => ! isset($blockedDrivers[$d->id])
            && ($d->licence_expires_on === null || $d->licence_expires_on->format('Y-m-d') >= $lastDay));

        return [
            'window' => ['from' => $from->toIso8601String(), 'until' => $until->toIso8601String()],
            'types' => $types,
            'drivers' => ['active' => $active->count(), 'free' => $free->count()],
        ];
    }

    /** @return array<int, true> ids with an unavailability overlapping [from, until) */
    private function blocked(string $column, CarbonInterface $from, CarbonInterface $until): array
    {
        return Unavailability::query()
            ->whereNotNull($column)
            ->where('starts_at', '<', $until->copy()->setTimezone('UTC'))
            ->where('ends_at', '>', $from->copy()->setTimezone('UTC'))
            ->pluck($column)
            ->flip()
            ->map(fn () => true)
            ->all();
    }
}
