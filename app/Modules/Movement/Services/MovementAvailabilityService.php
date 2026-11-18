<?php

namespace App\Modules\Movement\Services;

use App\Modules\Movement\Models\Booking;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\TripStop;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Vehicle and driver availability (plan §13.1–13.4, §13.7; Revision 6). Read-only: callers that write hold the
 * §5.2 row locks first (MovementTransaction), so these reads see the latest committed state.
 *
 * Windows are half-open [from, until): two windows overlap only when start < until AND end > from, so a window
 * that ends exactly when another starts is not an overlap.
 *
 * A resource is busy in a window when:
 * - it has an overlapping movement_unavailabilities row;
 * - it is assigned to another trip in a blocking status (scheduled, published, in_progress) whose
 *   blocked_from…blocked_until overlaps;
 * - it is reserved by a paired-trip span (§13.7): a pair whose wait flag is true for that resource, neither leg
 *   cancelled or merged, at least one leg in a blocking status, reserves [min(blocked_from), max(blocked_until)).
 *   A pair of draft trips reserves nothing, just as a draft trip blocks nothing (§13.1).
 *
 * summary() output is aggregate only — never per-vehicle or per-driver data (D7).
 */
class MovementAvailabilityService
{
    public function __construct(private SeatCapacityService $seats)
    {
    }

    public function vehicleIsFree(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $until, ?int $ignoreTripId = null): bool
    {
        return $this->vehicleConflicts($vehicle, $from, $until, $this->ignoreSet($ignoreTripId)) === [];
    }

    public function driverIsFree(Driver $driver, CarbonInterface $from, CarbonInterface $until, ?int $ignoreTripId = null): bool
    {
        return $this->driverConflicts($driver, $from, $until, $this->ignoreSet($ignoreTripId)) === [];
    }

    /**
     * Reasons the vehicle cannot serve [from, until); empty when it can.
     *
     * @param list<int> $ignoreTripIds trips being edited (a trip and its paired trip)
     * @return list<array{code: string, message: string}>
     */
    public function vehicleConflicts(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds = []): array
    {
        $conflicts = [];
        if ($vehicle->trashed() || $vehicle->operational_status !== Vehicle::STATUS_OPERATIONAL || ! $vehicle->isClassified()) {
            $conflicts[] = $this->conflict('vehicle_not_operational', ['vehicle' => $vehicle->fleet_number]);
        }

        foreach ($this->unavailabilities('vehicle_id', $vehicle->id, $from, $until) as $window) {
            $conflicts[] = $this->conflict('vehicle_unavailable', ['vehicle' => $vehicle->fleet_number] + $this->windowParams($window));
        }

        $busy = $this->busyTrips('vehicle_id', $vehicle->id, $from, $until, $ignoreTripIds);
        if ($busy !== []) {
            $conflicts[] = $this->conflict('vehicle_busy', ['vehicle' => $vehicle->fleet_number, 'trips' => implode(', ', $busy)]);
        }

        $reserved = $this->reservations('vehicle', $from, $until, $ignoreTripIds)[$vehicle->id] ?? [];
        if ($reserved !== []) {
            $conflicts[] = $this->conflict('vehicle_reserved', ['vehicle' => $vehicle->fleet_number, 'trips' => implode(', ', $reserved)]);
        }

        return $conflicts;
    }

    /**
     * Reasons the driver cannot serve [from, until); empty when they can.
     *
     * @param list<int> $ignoreTripIds
     * @return list<array{code: string, message: string}>
     */
    public function driverConflicts(Driver $driver, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds = []): array
    {
        $conflicts = [];
        if ($driver->trashed() || $driver->employment_status !== Driver::STATUS_ACTIVE) {
            $conflicts[] = $this->conflict('driver_not_active', ['driver' => $driver->name]);
        }

        if (! self::licenceValidFor($driver, $until)) {
            $conflicts[] = $this->conflict('driver_licence_expired', [
                'driver' => $driver->name,
                'date' => $driver->licence_expires_on->format('Y-m-d'),
            ]);
        }

        foreach ($this->unavailabilities('driver_id', $driver->id, $from, $until) as $window) {
            $conflicts[] = $this->conflict('driver_unavailable', ['driver' => $driver->name] + $this->windowParams($window));
        }

        $busy = $this->busyTrips('driver_id', $driver->id, $from, $until, $ignoreTripIds);
        if ($busy !== []) {
            $conflicts[] = $this->conflict('driver_busy', ['driver' => $driver->name, 'trips' => implode(', ', $busy)]);
        }

        $reserved = $this->reservations('driver', $from, $until, $ignoreTripIds)[$driver->id] ?? [];
        if ($reserved !== []) {
            $conflicts[] = $this->conflict('driver_reserved', ['driver' => $driver->name, 'trips' => implode(', ', $reserved)]);
        }

        return $conflicts;
    }

    /**
     * Licence rule (§13.2, D8, Revision 6): valid for [s, e) when the local expiry date is on or after the local date
     * of the last instant before e (e − 1 s). A missing expiry date means "not tracked" and is valid.
     */
    public static function licenceValidFor(Driver $driver, CarbonInterface $until): bool
    {
        if ($driver->licence_expires_on === null) {
            return true;
        }

        $lastDay = MovementTime::toLocal(CarbonImmutable::instance($until)->subSecond())->format('Y-m-d');

        return $driver->licence_expires_on->format('Y-m-d') >= $lastDay;
    }

    /** Operational, classified vehicles free in the window, optionally with at least $minCapacity seats. */
    public function freeVehicles(CarbonInterface $from, CarbonInterface $until, ?int $minCapacity = null, array $ignoreTripIds = []): Collection
    {
        $busy = $this->busyResourceIds('vehicle', $from, $until, $ignoreTripIds);

        return Vehicle::query()
            ->where('operational_status', Vehicle::STATUS_OPERATIONAL)
            ->whereNotNull('vehicle_type_id')
            ->where('passenger_capacity', '>=', max(1, (int) $minCapacity))
            ->orderBy('fleet_number')
            ->get()
            ->reject(fn (Vehicle $v) => isset($busy[$v->id]))
            ->values();
    }

    /** Active drivers with a valid licence who are free in the window. */
    public function freeDrivers(CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds = []): Collection
    {
        $busy = $this->busyResourceIds('driver', $from, $until, $ignoreTripIds);

        return Driver::query()
            ->where('employment_status', Driver::STATUS_ACTIVE)
            ->orderBy('name')
            ->get()
            ->reject(fn (Driver $d) => isset($busy[$d->id]) || ! self::licenceValidFor($d, $until))
            ->values();
    }

    public function freeDriverCount(CarbonInterface $from, CarbonInterface $until): int
    {
        return $this->freeDrivers($from, $until)->count();
    }

    /**
     * Public figures for the window [from, until): per vehicle type, and the free-driver count (§13.2, D7).
     *
     * @return array{window: array{from: string, until: string}, types: array<int, array>, drivers: array{active: int, free: int}}
     */
    public function summary(CarbonInterface $from, CarbonInterface $until, ?int $minCapacity = null): array
    {
        $busyVehicles = $this->busyResourceIds('vehicle', $from, $until);

        $vehicles = Vehicle::query()
            ->whereNotNull('vehicle_type_id')
            ->where('operational_status', '!=', Vehicle::STATUS_INACTIVE)
            ->get(['id', 'vehicle_type_id', 'passenger_capacity', 'operational_status'])
            ->groupBy('vehicle_type_id');

        $types = VehicleType::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (VehicleType $type) use ($vehicles, $busyVehicles, $minCapacity) {
                $group = $vehicles->get($type->id, collect());
                $free = $group->filter(fn (Vehicle $v) => $v->operational_status === Vehicle::STATUS_OPERATIONAL && ! isset($busyVehicles[$v->id]));

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

        $active = Driver::query()->where('employment_status', Driver::STATUS_ACTIVE)->count();

        return [
            'window' => ['from' => $from->toIso8601String(), 'until' => $until->toIso8601String()],
            'types' => $types,
            'drivers' => ['active' => $active, 'free' => $this->freeDriverCount($from, $until)],
        ];
    }

    /**
     * Everything that prevents draft → scheduled (§13.5, §15.3; Revision 6). Also the trip's conflicts panel:
     * unavailability windows and licence expiry that appear after assignment are reported here, not blocked.
     *
     * @return list<array{code: string, message: string}>
     */
    public function conflictsFor(Trip $trip): array
    {
        $conflicts = [];
        $stops = $trip->stops()->get();

        if ($stops->count() < 2) {
            $conflicts[] = $this->conflict('stops_missing');
        }
        $timesOk = $this->timesComplete($trip, $stops);
        if (! $timesOk) {
            $conflicts[] = $this->conflict('times_missing');
        } elseif (! $this->timesMonotonic($stops)) {
            $conflicts[] = $this->conflict('times_invalid');
            $timesOk = false;
        }

        $pair = $trip->paired_trip_id ? Trip::query()->find($trip->paired_trip_id) : null;
        $ignore = array_values(array_filter([$trip->id, $pair?->id]));
        $vehicle = $trip->vehicle_id ? Vehicle::withTrashed()->find($trip->vehicle_id) : null;
        $driver = $trip->driver_id ? Driver::withTrashed()->find($trip->driver_id) : null;

        if (! $vehicle) {
            $conflicts[] = $this->conflict('vehicle_missing');
        } elseif ($timesOk) {
            [$from, $until] = $this->resourceWindow($trip, $pair, 'vehicle');
            array_push($conflicts, ...$this->vehicleConflicts($vehicle, $from, $until, $ignore));
        }

        if (! $driver) {
            $conflicts[] = $this->conflict('driver_missing');
        } elseif ($timesOk) {
            [$from, $until] = $this->resourceWindow($trip, $pair, 'driver');
            array_push($conflicts, ...$this->driverConflicts($driver, $from, $until, $ignore));
        }

        if ($pair) {
            if ($trip->vehicle_waits_between && (int) $pair->vehicle_id !== (int) $trip->vehicle_id) {
                $conflicts[] = $this->conflict('pair_vehicle_mismatch', ['trip' => $pair->reference_no]);
            }
            if ($trip->driver_waits_between && (int) $pair->driver_id !== (int) $trip->driver_id) {
                $conflicts[] = $this->conflict('pair_driver_mismatch', ['trip' => $pair->reference_no]);
            }
        }

        $bookings = $trip->bookings()->counting()->with('requestLeg')->get();
        $sequence = $stops->pluck('sequence', 'id');
        if ($trip->seat_capacity === null) {
            $conflicts[] = $this->conflict('capacity_missing');
        } else {
            if ($vehicle && $vehicle->passenger_capacity !== null && $trip->seat_capacity > $vehicle->passenger_capacity) {
                $conflicts[] = $this->conflict('capacity_exceeds_vehicle', ['seats' => $trip->seat_capacity, 'capacity' => $vehicle->passenger_capacity]);
            }
            $load = $this->seats->maxLoad($stops->count(), $bookings->map(fn (Booking $b) => [
                'pickup' => $sequence[$b->pickup_stop_id], 'dropoff' => $sequence[$b->dropoff_stop_id], 'pax' => $b->passenger_count,
            ]));
            if ($load > $trip->seat_capacity) {
                $conflicts[] = $this->conflict('load_exceeds_capacity', ['load' => $load, 'seats' => $trip->seat_capacity]);
            }
        }

        if ($timesOk) {
            $arrivals = $stops->pluck('planned_arrival_at', 'id');
            foreach ($bookings as $booking) {
                $deadline = $booking->requestLeg?->required_arrival_at;
                $arrival = $arrivals[$booking->dropoff_stop_id] ?? null;
                if ($deadline && $arrival && $arrival->gt($deadline)) {
                    $conflicts[] = $this->conflict('deadline_missed', [
                        'arrival' => MovementTime::toLocal($arrival)->format('Y-m-d H:i'),
                        'deadline' => MovementTime::toLocal($deadline)->format('Y-m-d H:i'),
                    ]);
                }
            }
        }

        return $conflicts;
    }

    /**
     * The window a resource must be free in: the trip's own block, or the pair's span when the trip is paired and
     * the resource waits between the legs (§13.7).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function resourceWindow(Trip $trip, ?Trip $pair, string $resource): array
    {
        $from = CarbonImmutable::instance($trip->blocked_from);
        $until = CarbonImmutable::instance($trip->blocked_until);

        if ($pair && $trip->{$resource.'_waits_between'} && $pair->hasWindow()) {
            $from = $from->min($pair->blocked_from);
            $until = $until->max($pair->blocked_until);
        }

        return [$from, $until];
    }

    private function timesComplete(Trip $trip, Collection $stops): bool
    {
        if ($stops->count() < 2 || ! $trip->hasWindow() || ! $trip->planned_departure_at || ! $trip->planned_arrival_at) {
            return false;
        }

        $last = $stops->count() - 1;
        foreach ($stops->values() as $i => $stop) {
            if (($i > 0 && ! $stop->planned_arrival_at) || ($i < $last && ! $stop->planned_departure_at)) {
                return false;
            }
        }

        return true;
    }

    /** §13.5: stop k departure ≥ arrival, and stop k+1 arrival > stop k departure. */
    private function timesMonotonic(Collection $stops): bool
    {
        $previousDeparture = null;
        foreach ($stops->values() as $stop) {
            /** @var TripStop $stop */
            if ($previousDeparture && ! $stop->planned_arrival_at->gt($previousDeparture)) {
                return false;
            }
            if ($stop->planned_arrival_at && $stop->planned_departure_at && $stop->planned_departure_at->lt($stop->planned_arrival_at)) {
                return false;
            }
            $previousDeparture = $stop->planned_departure_at;
        }

        return true;
    }

    /** @return array<int, true> resource ids busy in the window for any reason */
    private function busyResourceIds(string $resource, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds = []): array
    {
        $column = $resource.'_id';
        $busy = $this->unavailableIds($column, $from, $until);

        $trips = $this->blockingTripQuery($column, $from, $until, $ignoreTripIds)->pluck($column);
        foreach ($trips as $id) {
            $busy[(int) $id] = true;
        }
        foreach (array_keys($this->reservations($resource, $from, $until, $ignoreTripIds)) as $id) {
            $busy[$id] = true;
        }

        return $busy;
    }

    /** @return list<string> references of blocking trips of this resource overlapping the window */
    private function busyTrips(string $column, int $id, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds): array
    {
        return $this->blockingTripQuery($column, $from, $until, $ignoreTripIds)->where($column, $id)
            ->orderBy('blocked_from')->pluck('reference_no')->all();
    }

    private function blockingTripQuery(string $column, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds)
    {
        return Trip::query()
            ->whereNotNull($column)
            ->whereIn('status', Trip::BLOCKING_STATUSES)
            ->where('blocked_from', '<', $this->utc($until))
            ->where('blocked_until', '>', $this->utc($from))
            ->when($ignoreTripIds !== [], fn ($q) => $q->whereNotIn('id', $ignoreTripIds));
    }

    /**
     * Paired-trip reservations overlapping the window (§13.7), keyed by resource id → trip references.
     *
     * @return array<int, list<string>>
     */
    private function reservations(string $resource, CarbonInterface $from, CarbonInterface $until, array $ignoreTripIds): array
    {
        $column = $resource.'_id';
        $flag = $resource.'_waits_between';

        $legs = Trip::query()
            ->whereNotNull('paired_trip_id')
            ->whereNotNull($column)
            ->where($flag, true)
            ->whereNotIn('status', Trip::ENDED_WITHOUT_SERVICE)
            ->with('pairedTrip')
            ->get();

        $reserved = [];
        foreach ($legs as $leg) {
            $pair = $leg->pairedTrip;
            if (! $pair || $leg->id > $pair->id && $pair->{$flag} && (int) $pair->{$column} === (int) $leg->{$column}) {
                continue; // each pair once (from its lower id), unless the legs disagree on the resource
            }
            if (in_array($leg->id, $ignoreTripIds, true) || in_array($pair->id, $ignoreTripIds, true)
                || in_array($pair->status, Trip::ENDED_WITHOUT_SERVICE, true)
                || ! $leg->hasWindow() || ! $pair->hasWindow()
                || (! in_array($leg->status, Trip::BLOCKING_STATUSES, true) && ! in_array($pair->status, Trip::BLOCKING_STATUSES, true))) {
                continue;
            }

            $spanFrom = CarbonImmutable::instance($leg->blocked_from)->min($pair->blocked_from);
            $spanUntil = CarbonImmutable::instance($leg->blocked_until)->max($pair->blocked_until);
            if ($spanFrom->lt($this->utc($until)) && $spanUntil->gt($this->utc($from))) {
                $reserved[(int) $leg->{$column}][] = $leg->reference_no.' ↔ '.$pair->reference_no;
            }
        }

        return $reserved;
    }

    private function unavailabilities(string $column, int $id, CarbonInterface $from, CarbonInterface $until): Collection
    {
        return Unavailability::query()
            ->where($column, $id)
            ->where('starts_at', '<', $this->utc($until))
            ->where('ends_at', '>', $this->utc($from))
            ->orderBy('starts_at')
            ->get();
    }

    /** @return array<int, true> */
    private function unavailableIds(string $column, CarbonInterface $from, CarbonInterface $until): array
    {
        return Unavailability::query()
            ->whereNotNull($column)
            ->where('starts_at', '<', $this->utc($until))
            ->where('ends_at', '>', $this->utc($from))
            ->pluck($column)
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    private function windowParams(Unavailability $window): array
    {
        return [
            'type' => __('movement.unavailability.types.'.$window->type),
            'from' => MovementTime::toLocal($window->starts_at)->format('Y-m-d H:i'),
            'until' => MovementTime::toLocal($window->ends_at)->format('Y-m-d H:i'),
        ];
    }

    private function ignoreSet(?int $tripId): array
    {
        if ($tripId === null) {
            return [];
        }

        $pairId = Trip::query()->whereKey($tripId)->value('paired_trip_id');

        return array_values(array_filter([$tripId, $pairId]));
    }

    private function utc(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone('UTC');
    }

    /** @return array{code: string, message: string} */
    private function conflict(string $code, array $params = []): array
    {
        return ['code' => $code, 'message' => __('movement.trips.conflicts.'.$code, $params)];
    }
}
