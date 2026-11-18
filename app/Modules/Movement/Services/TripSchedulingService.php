<?php

namespace App\Modules\Movement\Services;

use App\Models\User;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\Booking;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\TripStop;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Support\MovementAudit;
use App\Modules\Movement\Support\MovementTime;
use App\Modules\Movement\Support\MovementTransaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trip planning, timing, assignment, scheduling and request-leg allocation (plan §9.4, §11.4–11.6, §13.5–13.7,
 * §15.3; Revision 6). Every write runs in MovementTransaction with row locks in the §5.2 order (request legs, trips,
 * vehicles, drivers) and re-validates under those locks; the caller's view of the data is never trusted.
 *
 * Phase 5 trip transitions: draft → scheduled, scheduled → draft, draft/scheduled → cancelled (Revision 6).
 * Any edit of a scheduled trip must leave it valid (§15.3); otherwise it is refused and nothing changes.
 * Authorization is done by the caller (TripPolicy). Notifications arrive in 5A-2.
 */
class TripSchedulingService
{
    public function __construct(
        private SeatCapacityService $seats,
        private MovementAvailabilityService $availability,
        private MovementRequestWorkflow $workflow,
    ) {
    }

    // ------------------------------------------------------------------ creation and stops

    /** New draft trip, optionally starting from an approved request leg (§9.1 step 1): its origin and destination become the first stops. */
    public function createDraft(User $actor, ?MovementRequestLeg $fromLeg = null): Trip
    {
        return MovementTransaction::run(function () use ($actor, $fromLeg) {
            if ($fromLeg) {
                $fromLeg = MovementTransaction::lockLegs([$fromLeg->id])->first();
                $this->assertRequestApproved($fromLeg);
            }

            $trip = Trip::create([
                'status' => Trip::STATUS_DRAFT,
                'pre_trip_buffer_minutes' => (int) config('movement.buffers.pre_trip_minutes'),
                'post_trip_buffer_minutes' => (int) config('movement.buffers.post_trip_minutes'),
                'route_source' => 'manual',
                'created_by' => $actor->id,
            ]);
            // Insert first, then derive the reference from the id: unique by construction (Revision 6).
            $trip->update(['reference_no' => sprintf('MT-%s-%06d', CarbonImmutable::now(MovementTime::timezone())->format('Y'), $trip->id)]);

            if ($fromLeg) {
                $this->insertStop($trip, $this->placeFromLeg($fromLeg, 'origin') + ['allows_pickup' => true, 'allows_drop_off' => false], 1);
                $this->insertStop($trip, $this->placeFromLeg($fromLeg, 'destination') + ['allows_pickup' => false, 'allows_drop_off' => true], 2);
            }

            MovementAudit::record('trip.created', $trip, null, ['reference_no' => $trip->reference_no, 'from_request_leg_id' => $fromLeg?->id], $actor->id);

            return $trip->fresh();
        });
    }

    /**
     * Add a stop at $position (1-based; default: append). Later stops shift down, so the relative order of stops
     * that bookings reference is kept (§11.4). $data: location_id or name (+ address, maps_url), allows_pickup,
     * allows_drop_off, running_minutes (section ending here), distance_m, next_running_minutes (section after it, when inserting).
     */
    public function addStop(Trip $trip, User $actor, array $data, ?int $position = null): TripStop
    {
        return MovementTransaction::run(function () use ($trip, $actor, $data, $position) {
            $trip = $this->lockForEdit($trip);
            $count = $trip->stops()->count();
            $position = $position === null ? $count + 1 : max(1, min($position, $count + 1));

            $place = isset($data['location_id']) && $data['location_id'] ? $this->placeFromLocation((int) $data['location_id']) : $this->customPlace($data);
            $next = $trip->stops()->where('sequence', $position)->first();
            $stop = $this->insertStop($trip, $place + [
                'allows_pickup' => (bool) ($data['allows_pickup'] ?? true),
                'allows_drop_off' => (bool) ($data['allows_drop_off'] ?? true),
                'running_seconds_from_previous' => $position > 1 ? $this->minutesToSeconds($data['running_minutes'] ?? null) : null,
                'distance_m_from_previous' => $position > 1 ? ($data['distance_m'] ?? null) : null,
            ], $position);

            if ($next) {
                // The stop that followed the insertion point now starts a new section.
                $next->update(['running_seconds_from_previous' => $this->minutesToSeconds($data['next_running_minutes'] ?? null), 'distance_m_from_previous' => null]);
            }

            $this->retime($trip);
            $this->assertStillValidIfScheduled($trip);
            MovementAudit::record('trip.stop_added', $trip, null, ['stop_id' => $stop->id, 'sequence' => $stop->sequence, 'name' => $stop->name], $actor->id);

            return $stop->fresh();
        });
    }

    /** Edit a stop's flags, snapshot fields or section values. A flag used by a booking cannot be switched off (§11.4). */
    public function updateStop(Trip $trip, TripStop $stop, User $actor, array $data): TripStop
    {
        return MovementTransaction::run(function () use ($trip, $stop, $actor, $data) {
            $trip = $this->lockForEdit($trip);
            $stop = $this->stopOf($trip, $stop->id);
            $before = $stop->only(['name', 'address', 'maps_url', 'allows_pickup', 'allows_drop_off', 'running_seconds_from_previous', 'distance_m_from_previous']);

            $changes = array_intersect_key($data, array_flip(['name', 'address', 'maps_url', 'allows_pickup', 'allows_drop_off', 'distance_m']));
            if (array_key_exists('running_minutes', $data) && $stop->sequence > 1) {
                $changes['running_seconds_from_previous'] = $this->minutesToSeconds($data['running_minutes']);
            }
            if (array_key_exists('distance_m', $changes)) {
                $changes['distance_m_from_previous'] = $stop->sequence > 1 ? $changes['distance_m'] : null;
                unset($changes['distance_m']);
            }

            $active = Booking::query()->where('movement_trip_id', $trip->id)->whereIn('status', Booking::ACTIVE_STATUSES);
            if (($changes['allows_pickup'] ?? true) === false && (clone $active)->where('pickup_stop_id', $stop->id)->exists()) {
                throw new IllegalMovementTransition(__('movement.trips.errors.stop_flag_in_use'));
            }
            if (($changes['allows_drop_off'] ?? true) === false && (clone $active)->where('dropoff_stop_id', $stop->id)->exists()) {
                throw new IllegalMovementTransition(__('movement.trips.errors.stop_flag_in_use'));
            }

            $stop->update($changes);
            $this->retime($trip);
            $this->assertStillValidIfScheduled($trip);
            [$old, $new] = MovementAudit::diff($before, $stop);
            if ($new) {
                MovementAudit::record('trip.stop_updated', $trip, ['stop_id' => $stop->id] + $old, ['stop_id' => $stop->id] + $new, $actor->id);
            }

            return $stop->fresh();
        });
    }

    /** Remove a stop that no booking references (§7.9, §11.4). The two sections around it merge (running times add up). */
    public function removeStop(Trip $trip, TripStop $stop, User $actor): void
    {
        MovementTransaction::run(function () use ($trip, $stop, $actor) {
            $trip = $this->lockForEdit($trip);
            $stop = $this->stopOf($trip, $stop->id);
            if (Booking::query()->where('pickup_stop_id', $stop->id)->orWhere('dropoff_stop_id', $stop->id)->exists()) {
                throw new IllegalMovementTransition(__('movement.trips.errors.stop_referenced'));
            }

            $next = $trip->stops()->where('sequence', $stop->sequence + 1)->first();
            if ($next && $stop->sequence > 1) {
                $both = $stop->running_seconds_from_previous !== null && $next->running_seconds_from_previous !== null;
                $next->update([
                    'running_seconds_from_previous' => $both ? $stop->running_seconds_from_previous + $next->running_seconds_from_previous : null,
                    'distance_m_from_previous' => $stop->distance_m_from_previous !== null && $next->distance_m_from_previous !== null
                        ? $stop->distance_m_from_previous + $next->distance_m_from_previous : null,
                ]);
            } elseif ($next) {
                $next->update(['running_seconds_from_previous' => null, 'distance_m_from_previous' => null]); // it becomes the first stop
            }

            $snapshot = $stop->only(['id', 'sequence', 'name']);
            $stop->delete();
            $this->renumber($trip, $trip->stops()->pluck('id')->all());
            $this->retime($trip);
            $this->assertStillValidIfScheduled($trip);
            MovementAudit::record('trip.stop_removed', $trip, $snapshot, null, $actor->id);
        });
    }

    /**
     * Reorder the stops. A stop that a booking references keeps its position (§11.4: reordering a referenced stop is
     * blocked). Sections change, so running times and planned times are cleared and the trip must be timed again.
     *
     * @param list<int> $orderedStopIds every stop of the trip, in the new order
     */
    public function reorderStops(Trip $trip, User $actor, array $orderedStopIds): void
    {
        MovementTransaction::run(function () use ($trip, $actor, $orderedStopIds) {
            $trip = $this->lockForEdit($trip);
            $stops = $trip->stops()->get();
            $orderedStopIds = array_map('intval', array_values($orderedStopIds));
            $current = array_map('intval', $stops->pluck('id')->all());
            if (count($orderedStopIds) !== count($current) || array_diff($current, $orderedStopIds) !== []) {
                throw new IllegalMovementTransition(__('movement.trips.errors.reorder_incomplete'));
            }
            if ($orderedStopIds === $current) {
                return;
            }

            $referenced = Booking::query()->where('movement_trip_id', $trip->id)
                ->get(['pickup_stop_id', 'dropoff_stop_id'])->flatMap(fn (Booking $b) => [$b->pickup_stop_id, $b->dropoff_stop_id])->unique();
            foreach ($referenced as $stopId) {
                if (array_search($stopId, $orderedStopIds, true) !== array_search($stopId, $current, true)) {
                    throw new IllegalMovementTransition(__('movement.trips.errors.stop_referenced'));
                }
            }

            $this->renumber($trip, $orderedStopIds);
            TripStop::query()->where('movement_trip_id', $trip->id)->update([
                'running_seconds_from_previous' => null, 'distance_m_from_previous' => null, 'planned_arrival_at' => null, 'planned_departure_at' => null,
            ]);
            $this->clearTimetable($trip);
            $this->assertStillValidIfScheduled($trip);
            MovementAudit::record('trip.stops_reordered', $trip, ['order' => $current], ['order' => $orderedStopIds], $actor->id);
        });
    }

    // ------------------------------------------------------------------ timetable (§9.4)

    /**
     * Set the section running times (free-flow minutes per stop k ≥ 2), dwell minutes (intermediate stops) and the
     * anchor, then compute every planned time (§9.4). Anchor:
     * - ['type' => 'depart_at', 'at' => UTC datetime];
     * - ['type' => 'arrive_by', 'at' => UTC datetime, 'stop_id' => id]: departure = at − offset(stop), rounded down to 5 min;
     * - ['type' => 'latest']: the latest departure that meets every booking deadline (rounded down to 5 min).
     * The departure may be earlier than the latest one, never later.
     *
     * @param array<int, int> $runningMinutes stop id → free-flow minutes of the section ending at that stop
     * @param array<int, int> $dwellMinutes stop id → dwell minutes (defaults: current dwell, else config)
     */
    public function setTimetable(Trip $trip, User $actor, array $runningMinutes, array $dwellMinutes, array $anchor): Trip
    {
        return MovementTransaction::run(function () use ($trip, $actor, $runningMinutes, $dwellMinutes, $anchor) {
            $trip = $this->lockForEdit($trip);
            $stops = $trip->stops()->get();
            if ($stops->count() < 2) {
                throw new IllegalMovementTransition(__('movement.trips.errors.two_stops'));
            }

            foreach ($stops->slice(1) as $stop) {
                $minutes = $runningMinutes[$stop->id] ?? null;
                $seconds = $minutes === null ? $stop->running_seconds_from_previous : $this->minutesToSeconds($minutes);
                if ($seconds === null || $seconds < 60) {
                    throw new IllegalMovementTransition(__('movement.trips.errors.running_required'));
                }
                $stop->running_seconds_from_previous = $seconds;
            }
            $dwells = $this->dwellSeconds($stops, $dwellMinutes);
            $offsets = $this->arrivalOffsets($stops, $dwells);
            $latest = $this->latestDepartureFor($trip, $stops, $offsets);

            $departure = match ($anchor['type'] ?? null) {
                'depart_at' => CarbonImmutable::instance($anchor['at'])->setTimezone('UTC'),
                'arrive_by' => $this->floorToFiveMinutes(CarbonImmutable::instance($anchor['at'])->setTimezone('UTC')
                    ->subSeconds($offsets[(int) ($anchor['stop_id'] ?? 0)] ?? throw new IllegalMovementTransition(__('movement.trips.errors.anchor_stop')))),
                'latest' => $latest ?? throw new IllegalMovementTransition(__('movement.trips.errors.no_deadline')),
                default => throw new IllegalMovementTransition(__('movement.trips.errors.anchor_stop')),
            };
            if ($latest && $departure->gt($latest)) {
                throw new IllegalMovementTransition(__('movement.trips.errors.departure_after_latest', ['latest' => MovementTime::toLocal($latest)->format('Y-m-d H:i')]));
            }

            $before = $trip->only(['planned_departure_at', 'planned_arrival_at']);
            $this->applyTimes($trip, $stops, $dwells, $offsets, $departure);
            $this->assertStillValidIfScheduled($trip);
            MovementAudit::record('trip.timetable_set', $trip, $this->timesForAudit($before), $this->timesForAudit($trip->only(['planned_departure_at', 'planned_arrival_at'])), $actor->id);

            return $trip->fresh();
        });
    }

    /** The latest first departure that meets every request-booking deadline, rounded down to 5 minutes (§9.4); null without deadlines or running times. */
    public function latestDeparture(Trip $trip): ?CarbonImmutable
    {
        $stops = $trip->stops()->get();
        if ($stops->count() < 2 || $stops->slice(1)->contains(fn (TripStop $s) => $s->running_seconds_from_previous === null)) {
            return null;
        }

        return $this->latestDepartureFor($trip, $stops, $this->arrivalOffsets($stops, $this->dwellSeconds($stops, [])));
    }

    // ------------------------------------------------------------------ capacity and assignment (§13.5–13.6)

    /** Set the seats offered: ≥ 1, ≥ the current max section load, ≤ the assigned vehicle's capacity (§13.5, Revision 6). */
    public function setSeatCapacity(Trip $trip, User $actor, int $seats): Trip
    {
        return MovementTransaction::run(function () use ($trip, $actor, $seats) {
            $trip = $this->lockForEdit($trip);
            $vehicle = $trip->vehicle_id ? MovementTransaction::lockVehicles([$trip->vehicle_id])->first() : null;
            $this->assertSeatCapacity($trip, $vehicle, $seats);

            $old = $trip->seat_capacity;
            $trip->update(['seat_capacity' => $seats]);
            $this->assertStillValidIfScheduled($trip);
            MovementAudit::record('trip.seat_capacity_changed', $trip, ['seat_capacity' => $old], ['seat_capacity' => $seats], $actor->id);

            return $trip;
        });
    }

    /**
     * Assign, change or remove the vehicle and the driver (§13.5, §13.6, §13.7). Pass the current id to keep a resource
     * and null to remove it. The new resources must be free in the trip's window (the pair's span when the resource
     * waits between paired legs, and then both legs get the same resource in this transaction). seat_capacity becomes
     * $seatCapacity, else min(previous, vehicle capacity), else the vehicle capacity. Removing a resource from a
     * scheduled trip returns it to draft and refreshes the fulfilment of the requests it serves.
     */
    public function assign(Trip $trip, User $actor, ?int $vehicleId, ?int $driverId, ?int $seatCapacity = null): Trip
    {
        return MovementTransaction::run(function () use ($trip, $actor, $vehicleId, $driverId, $seatCapacity) {
            [$trip, $pair, $legs] = $this->lockTripContext($trip);
            $this->assertEditable($trip);

            $vehicles = MovementTransaction::lockVehicles([$trip->vehicle_id, $vehicleId, $pair?->vehicle_id]);
            $drivers = MovementTransaction::lockDrivers([$trip->driver_id, $driverId, $pair?->driver_id]);
            $vehicle = $vehicleId ? $vehicles->get($vehicleId) : null;
            $driver = $driverId ? $drivers->get($driverId) : null;
            if (($vehicleId && ! $vehicle) || ($driverId && ! $driver)) {
                throw new IllegalMovementTransition(__('movement.trips.errors.resource_missing'));
            }

            $ignore = array_values(array_filter([$trip->id, $pair?->id]));
            $vehicleChanged = (int) $trip->vehicle_id !== (int) $vehicleId;
            $driverChanged = (int) $trip->driver_id !== (int) $driverId;

            if ($vehicle && $vehicleChanged) {
                $this->assertResourceFree($trip, $pair, 'vehicle', fn ($from, $until) => $this->availability->vehicleConflicts($vehicle, $from, $until, $ignore));
            }
            if ($driver && $driverChanged) {
                $this->assertResourceFree($trip, $pair, 'driver', fn ($from, $until) => $this->availability->driverConflicts($driver, $from, $until, $ignore));
            }

            $seats = $seatCapacity ?? ($vehicle ? ($trip->seat_capacity !== null ? min($trip->seat_capacity, (int) $vehicle->passenger_capacity) : (int) $vehicle->passenger_capacity) : $trip->seat_capacity);
            if ($seats !== null && ($seatCapacity !== null || $vehicleChanged)) {
                $this->assertSeatCapacity($trip, $vehicle, $seats);
            }

            $before = $trip->only(['vehicle_id', 'driver_id', 'seat_capacity', 'status']);
            $trip->update(['vehicle_id' => $vehicleId, 'driver_id' => $driverId, 'seat_capacity' => $seats]);

            // §13.7: a resource that waits between paired legs must be the same on both legs.
            if ($pair && $trip->vehicle_waits_between && $vehicleChanged) {
                $pair->update(['vehicle_id' => $vehicleId]);
            }
            if ($pair && $trip->driver_waits_between && $driverChanged) {
                $pair->update(['driver_id' => $driverId]);
            }

            foreach (array_filter([$trip, $pair]) as $leg) {
                if ($leg->status === Trip::STATUS_SCHEDULED && (! $leg->vehicle_id || ! $leg->driver_id)) {
                    $leg->update(['status' => Trip::STATUS_DRAFT]); // §13.6, §15.3: resources released
                    MovementAudit::record('trip.unscheduled', $leg, ['status' => Trip::STATUS_SCHEDULED], ['status' => Trip::STATUS_DRAFT, 'comment' => 'resource removed'], $actor->id);
                }
            }
            if ($trip->status === Trip::STATUS_SCHEDULED) {
                $this->assertValid($trip);
            }
            if ($pair && $pair->status === Trip::STATUS_SCHEDULED) {
                $this->assertValid($pair);
            }

            $this->auditAssignment($trip, $before, $actor);
            $this->workflow->refreshFulfilmentForLegs($legs->keys());

            return $trip->fresh();
        });
    }

    // ------------------------------------------------------------------ pairing (§13.7)

    /** Link an outbound and a return trip. driver_waits_between defaults to the vehicle flag (D22, Revision 6). */
    public function pair(Trip $first, Trip $second, User $actor, bool $vehicleWaits, ?bool $driverWaits = null): void
    {
        $driverWaits ??= $vehicleWaits;

        MovementTransaction::run(function () use ($first, $second, $actor, $vehicleWaits, $driverWaits) {
            $trips = MovementTransaction::lockTrips([$first->id, $second->id]);
            [$a, $b] = [$trips->get($first->id), $trips->get($second->id)];
            if (! $a || ! $b || $a->id === $b->id) {
                throw new IllegalMovementTransition(__('movement.trips.errors.pair_invalid'));
            }
            foreach ([$a, $b] as $trip) {
                $this->assertEditable($trip);
                if ($trip->paired_trip_id && ! in_array((int) $trip->paired_trip_id, [$a->id, $b->id], true)) {
                    throw new IllegalMovementTransition(__('movement.trips.errors.already_paired', ['trip' => $trip->reference_no]));
                }
            }
            if (($vehicleWaits && (int) $a->vehicle_id !== (int) $b->vehicle_id) || ($driverWaits && (int) $a->driver_id !== (int) $b->driver_id)) {
                throw new IllegalMovementTransition(__('movement.trips.errors.pair_resources_differ'));
            }
            MovementTransaction::lockVehicles([$a->vehicle_id, $b->vehicle_id]);
            MovementTransaction::lockDrivers([$a->driver_id, $b->driver_id]);

            $flags = ['vehicle_waits_between' => $vehicleWaits, 'driver_waits_between' => $driverWaits];
            $a->update(['paired_trip_id' => $b->id] + $flags);
            $b->update(['paired_trip_id' => $a->id] + $flags);

            // The span between the legs must be free for every waiting resource of a scheduled leg (§13.7).
            foreach ([$a, $b] as $trip) {
                if ($trip->status === Trip::STATUS_SCHEDULED) {
                    $this->assertValid($trip);
                }
            }

            MovementAudit::record('trip.paired', $a, null, ['paired_trip_id' => $b->id] + $flags, $actor->id);
            MovementAudit::record('trip.paired', $b, null, ['paired_trip_id' => $a->id] + $flags, $actor->id);
        });
    }

    public function unpair(Trip $trip, User $actor): void
    {
        MovementTransaction::run(function () use ($trip, $actor) {
            [$trip, $pair] = $this->lockTripContext($trip);
            if (! $pair) {
                return;
            }
            $this->clearPairing($trip, $pair, $actor);
        });
    }

    // ------------------------------------------------------------------ lifecycle (§15.3, Phase 5 subset)

    /** draft → scheduled: every §13.5 check passes; the vehicle and driver become busy in the blocked window. */
    public function schedule(Trip $trip, User $actor): Trip
    {
        return MovementTransaction::run(function () use ($trip, $actor) {
            [$trip, $pair, $legs] = $this->lockTripContext($trip);
            $this->guardTransition($trip, Trip::STATUS_SCHEDULED);
            MovementTransaction::lockVehicles([$trip->vehicle_id, $pair?->vehicle_id]);
            MovementTransaction::lockDrivers([$trip->driver_id, $pair?->driver_id]);
            $this->assertValid($trip);

            $trip->update(['status' => Trip::STATUS_SCHEDULED]);
            MovementAudit::record('trip.scheduled', $trip, ['status' => Trip::STATUS_DRAFT], ['status' => Trip::STATUS_SCHEDULED], $actor->id);
            $this->workflow->refreshFulfilmentForLegs($legs->keys());

            return $trip;
        });
    }

    /** scheduled → draft: resources are released; bookings stay. */
    public function unschedule(Trip $trip, User $actor): Trip
    {
        return MovementTransaction::run(function () use ($trip, $actor) {
            [$trip, , $legs] = $this->lockTripContext($trip);
            $this->guardTransition($trip, Trip::STATUS_DRAFT);

            $trip->update(['status' => Trip::STATUS_DRAFT]);
            MovementAudit::record('trip.unscheduled', $trip, ['status' => Trip::STATUS_SCHEDULED], ['status' => Trip::STATUS_DRAFT], $actor->id);
            $this->workflow->refreshFulfilmentForLegs($legs->keys());

            return $trip;
        });
    }

    /** draft/scheduled → cancelled with a reason: active bookings are cancelled, the pairing is cleared (§13.7, §15.3). */
    public function cancel(Trip $trip, User $actor, ?string $reason): Trip
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw new IllegalMovementTransition(__('movement.trips.errors.reason_required'));
        }

        return MovementTransaction::run(function () use ($trip, $actor, $reason) {
            [$trip, $pair, $legs] = $this->lockTripContext($trip);
            $this->guardTransition($trip, Trip::STATUS_CANCELLED);

            $from = $trip->status;
            foreach (Booking::query()->where('movement_trip_id', $trip->id)->whereIn('status', Booking::ACTIVE_STATUSES)->get() as $booking) {
                $this->cancelBooking($booking, $actor, $reason);
            }
            if ($pair) {
                $this->clearPairing($trip, $pair, $actor);
            }
            $trip->update(['status' => Trip::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $actor->id, 'cancellation_reason' => $reason]);
            MovementAudit::record('trip.cancelled', $trip, ['status' => $from], ['status' => Trip::STATUS_CANCELLED, 'comment' => $reason], $actor->id);
            $this->workflow->refreshFulfilmentForLegs($legs->keys());

            return $trip;
        });
    }

    // ------------------------------------------------------------------ request-leg allocation (§11.6)

    /** Allocate $pax passengers of an approved leg to a trip span, as a confirmed request booking (§11.6 steps 1–6). */
    public function allocateRequestLeg(MovementRequestLeg $leg, Trip $trip, TripStop $pickup, TripStop $dropoff, int $pax, User $actor): Booking
    {
        return MovementTransaction::run(function () use ($leg, $trip, $pickup, $dropoff, $pax, $actor) {
            $leg = MovementTransaction::lockLegs([$leg->id])->first();
            $trip = MovementTransaction::lockTrips([$trip->id])->first();
            $request = $this->assertRequestApproved($leg);
            $this->assertAllocatable($trip);

            [$pickup, $dropoff] = $this->assertSpan($trip, $pickup->id, $dropoff->id);
            $this->assertLegHasRoom($leg, $pax);
            $this->assertSectionsFit($trip, $pickup, $dropoff, $pax);

            $booking = Booking::create([
                'movement_trip_id' => $trip->id,
                'request_leg_id' => $leg->id,
                'source' => Booking::SOURCE_REQUEST,
                'booked_by_user_id' => $request->requester_id,
                'branch_id' => $request->branch_id,
                'context_type' => $request->context_type,
                'context_id' => $request->context_id,
                'context_label' => $request->context_label,
                'pickup_stop_id' => $pickup->id,
                'dropoff_stop_id' => $dropoff->id,
                'passenger_count' => $pax,
                'status' => Booking::STATUS_CONFIRMED,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            $this->assertStillValidIfScheduled($trip);
            $this->workflow->refreshFulfilment($request);
            MovementAudit::record('booking.allocated', $booking, null, $booking->only(['movement_trip_id', 'request_leg_id', 'pickup_stop_id', 'dropoff_stop_id', 'passenger_count', 'status']), $actor->id);

            return $booking;
        });
    }

    /** Release a request booking, or reduce it by $passengers (Revision 6). Same locks as allocation: the leg, then the trip. */
    public function deallocate(Booking $booking, User $actor, ?int $passengers = null, ?string $reason = null): Booking
    {
        return MovementTransaction::run(function () use ($booking, $actor, $passengers, $reason) {
            $booking = $this->lockRequestBooking($booking, [$booking->movement_trip_id]);
            $this->assertAllocatable($booking->trip);

            $this->reduceBooking($booking, $passengers, $actor, $reason);
            $this->workflow->refreshFulfilmentForLegs([$booking->request_leg_id]);

            return $booking->fresh();
        });
    }

    /**
     * Move $pax passengers of a request booking to another trip span in one transaction (§11.6 re-allocation):
     * lock the leg, then both trips (ascending id); reduce or cancel the old booking; insert the new one; recheck both.
     */
    public function reallocate(Booking $booking, Trip $toTrip, TripStop $pickup, TripStop $dropoff, int $pax, User $actor): Booking
    {
        return MovementTransaction::run(function () use ($booking, $toTrip, $pickup, $dropoff, $pax, $actor) {
            $booking = $this->lockRequestBooking($booking, [$booking->movement_trip_id, $toTrip->id]);
            $from = $booking->trip;
            $to = Trip::query()->findOrFail($toTrip->id);
            if ($from->id === $to->id) {
                throw new IllegalMovementTransition(__('movement.trips.errors.same_trip'));
            }
            if ($pax < 1 || $pax > $booking->passenger_count) {
                throw new IllegalMovementTransition(__('movement.trips.errors.reallocate_count', ['max' => $booking->passenger_count]));
            }
            $this->assertAllocatable($from);
            $this->assertAllocatable($to);
            $request = $this->assertRequestApproved($booking->requestLeg);

            [$pickup, $dropoff] = $this->assertSpan($to, $pickup->id, $dropoff->id);
            $this->reduceBooking($booking, $pax, $actor, __('movement.trips.reallocated_to', ['trip' => $to->reference_no]));
            $this->assertLegHasRoom($booking->requestLeg, $pax);
            $this->assertSectionsFit($to, $pickup, $dropoff, $pax);

            $moved = Booking::create($booking->only([
                'request_leg_id', 'source', 'booked_by_user_id', 'branch_id', 'context_type', 'context_id', 'context_label',
            ]) + [
                'movement_trip_id' => $to->id,
                'pickup_stop_id' => $pickup->id,
                'dropoff_stop_id' => $dropoff->id,
                'passenger_count' => $pax,
                'status' => Booking::STATUS_CONFIRMED,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            $this->assertStillValidIfScheduled($to);
            $this->workflow->refreshFulfilment($request);
            MovementAudit::record('booking.reallocated', $moved, ['booking_id' => $booking->id, 'movement_trip_id' => $from->id], ['movement_trip_id' => $to->id, 'passenger_count' => $pax], $actor->id);

            return $moved;
        });
    }

    // ------------------------------------------------------------------ resource guards (§13.6, Revision 6)

    /** Non-terminal trips that use the resource. */
    public function blockingTripsFor(string $resource, int $id): Collection
    {
        return Trip::query()->where($resource.'_id', $id)->whereIn('status', Trip::NON_TERMINAL_STATUSES)->orderBy('id')->get();
    }

    /** Refuse a vehicle status change / soft delete or a driver deactivation / soft delete while the resource is on a non-terminal trip. */
    public function guardResourceWithdrawal(string $resource, int $id): void
    {
        $trips = $this->blockingTripsFor($resource, $id);
        if ($trips->isNotEmpty()) {
            throw new IllegalMovementTransition(__('movement.validation.resource_on_trips', ['trips' => $trips->pluck('reference_no')->implode(', ')]));
        }
    }

    /** §13.6: a vehicle's capacity cannot drop below the seat_capacity of a non-terminal trip that uses it. */
    public function guardVehicleCapacity(Vehicle $vehicle, int $newCapacity): void
    {
        $trips = $this->blockingTripsFor('vehicle', $vehicle->id)->filter(fn (Trip $t) => $t->seat_capacity !== null && $t->seat_capacity > $newCapacity);
        if ($trips->isNotEmpty()) {
            throw new IllegalMovementTransition(__('movement.validation.capacity_below_trips', ['trips' => $trips->pluck('reference_no')->implode(', ')]));
        }
    }

    // ------------------------------------------------------------------ internals

    /**
     * Locks for an operation that may change fulfilment: the request legs served by the trip, then the trip and its
     * paired trip (ascending id). A leg that gained a booking while we waited for the trip lock is locked afterwards
     * (out of order; a resulting deadlock is retried by MovementTransaction).
     *
     * @return array{0: Trip, 1: ?Trip, 2: Collection<int, MovementRequestLeg>}
     */
    private function lockTripContext(Trip $trip): array
    {
        $legIds = fn (int $tripId) => Booking::query()->where('movement_trip_id', $tripId)->whereNotNull('request_leg_id')->pluck('request_leg_id')->unique()->all();

        $legs = MovementTransaction::lockLegs($legIds($trip->id));
        $pairId = Trip::query()->whereKey($trip->id)->value('paired_trip_id');
        $trips = MovementTransaction::lockTrips([$trip->id, $pairId]);
        $locked = $trips->get($trip->id) ?? throw new IllegalMovementTransition(__('movement.trips.errors.not_found'));

        if ((int) $locked->paired_trip_id !== (int) $pairId && $locked->paired_trip_id) {
            $trips = $trips->union(MovementTransaction::lockTrips([$locked->paired_trip_id]));
        }
        $missing = array_diff($legIds($locked->id), $legs->keys()->all());
        if ($missing !== []) {
            $legs = $legs->union(MovementTransaction::lockLegs($missing));
        }

        return [$locked, $locked->paired_trip_id ? $trips->get((int) $locked->paired_trip_id) : null, $legs];
    }

    /** Lock for a stop, time or capacity edit: the trip, its pair, and its resources (the window may move). */
    private function lockForEdit(Trip $trip): Trip
    {
        [$trip, $pair] = $this->lockTripContext($trip);
        $this->assertEditable($trip);
        MovementTransaction::lockVehicles([$trip->vehicle_id, $pair?->vehicle_id]);
        MovementTransaction::lockDrivers([$trip->driver_id, $pair?->driver_id]);

        return $trip;
    }

    /** Lock a request booking's leg, then the given trips; return the fresh booking. */
    private function lockRequestBooking(Booking $booking, array $tripIds): Booking
    {
        $booking = Booking::query()->findOrFail($booking->id);
        if ($booking->source !== Booking::SOURCE_REQUEST || ! $booking->request_leg_id) {
            throw new IllegalMovementTransition(__('movement.trips.errors.not_request_booking'));
        }
        MovementTransaction::lockLegs([$booking->request_leg_id]);
        MovementTransaction::lockTrips($tripIds);
        $booking = Booking::query()->with(['trip', 'requestLeg'])->findOrFail($booking->id);
        if ($booking->status !== Booking::STATUS_CONFIRMED) {
            throw new IllegalMovementTransition(__('movement.trips.errors.booking_not_active'));
        }

        return $booking;
    }

    private function reduceBooking(Booking $booking, ?int $passengers, User $actor, ?string $reason): void
    {
        if ($passengers !== null && $passengers < 1) {
            throw new IllegalMovementTransition(__('movement.trips.errors.reallocate_count', ['max' => $booking->passenger_count]));
        }

        if ($passengers === null || $passengers >= $booking->passenger_count) {
            $this->cancelBooking($booking, $actor, $reason ?? __('movement.trips.deallocated'));

            return;
        }

        $old = $booking->passenger_count;
        $booking->update(['passenger_count' => $old - $passengers]);
        MovementAudit::record('booking.reduced', $booking, ['passenger_count' => $old], ['passenger_count' => $booking->passenger_count, 'comment' => $reason], $actor->id);
    }

    private function cancelBooking(Booking $booking, User $actor, string $reason): void
    {
        $old = $booking->status;
        $booking->update(['status' => Booking::STATUS_CANCELLED, 'cancelled_by' => $actor->id, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
        MovementAudit::record('booking.cancelled', $booking, ['status' => $old], ['status' => Booking::STATUS_CANCELLED, 'comment' => $reason], $actor->id);
    }

    private function assertRequestApproved(?MovementRequestLeg $leg): MovementRequest
    {
        $request = $leg ? MovementRequest::query()->find($leg->movement_request_id) : null;
        if (! $request || $request->status !== MovementRequest::STATUS_APPROVED) {
            throw new IllegalMovementTransition(__('movement.trips.errors.request_not_approved'));
        }

        return $request;
    }

    private function assertAllocatable(Trip $trip): void
    {
        if (! in_array($trip->status, Trip::ALLOCATABLE_STATUSES, true)) {
            throw new IllegalMovementTransition(__('movement.trips.errors.not_allocatable'));
        }
        if ($trip->seat_capacity === null) {
            throw new IllegalMovementTransition(__('movement.trips.errors.capacity_missing'));
        }
    }

    /** Leg aggregate (§11.6): confirmed request bookings of the leg on trips that count as allocated. */
    private function assertLegHasRoom(MovementRequestLeg $leg, int $pax): void
    {
        if ($pax < 1) {
            throw new IllegalMovementTransition(__('movement.trips.errors.pax_positive'));
        }

        $allocated = (int) Booking::query()
            ->where('request_leg_id', $leg->id)
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereHas('trip', fn ($q) => $q->whereIn('status', Trip::ALLOCATED_STATUSES))
            ->sum('passenger_count');

        if ($allocated + $pax > $leg->passenger_count) {
            throw new IllegalMovementTransition(__('movement.trips.errors.leg_over_allocated', [
                'remaining' => max(0, $leg->passenger_count - $allocated),
            ]));
        }
    }

    /** Section capacity (§11.4) for the trip's counting bookings plus the new span. */
    private function assertSectionsFit(Trip $trip, TripStop $pickup, TripStop $dropoff, int $pax): void
    {
        $stops = $trip->stops()->get();
        $sequence = $stops->pluck('sequence', 'id');
        $spans = Booking::query()->where('movement_trip_id', $trip->id)->counting()->get()
            ->map(fn (Booking $b) => ['pickup' => $sequence[$b->pickup_stop_id], 'dropoff' => $sequence[$b->dropoff_stop_id], 'pax' => $b->passenger_count]);

        $loads = $this->seats->loads($stops->count(), $spans);
        $available = $this->seats->available((int) $trip->seat_capacity, $loads, $pickup->sequence, $dropoff->sequence);
        if ($available < $pax) {
            throw new IllegalMovementTransition(__('movement.trips.errors.no_seats', ['available' => max(0, $available)]));
        }
    }

    /** @return array{0: TripStop, 1: TripStop} */
    private function assertSpan(Trip $trip, int $pickupId, int $dropoffId): array
    {
        $pickup = TripStop::query()->where('movement_trip_id', $trip->id)->find($pickupId);
        $dropoff = TripStop::query()->where('movement_trip_id', $trip->id)->find($dropoffId);
        if (! $pickup || ! $dropoff || $pickup->sequence >= $dropoff->sequence || ! $pickup->allows_pickup || ! $dropoff->allows_drop_off) {
            throw new IllegalMovementTransition(__('movement.trips.errors.span_invalid'));
        }

        return [$pickup, $dropoff];
    }

    private function assertSeatCapacity(Trip $trip, ?Vehicle $vehicle, int $seats): void
    {
        if ($seats < 1) {
            throw new IllegalMovementTransition(__('movement.trips.errors.pax_positive'));
        }
        if ($vehicle && $vehicle->passenger_capacity !== null && $seats > $vehicle->passenger_capacity) {
            throw new IllegalMovementTransition(__('movement.trips.errors.seats_over_vehicle', ['capacity' => $vehicle->passenger_capacity]));
        }

        $stops = $trip->stops()->get();
        $sequence = $stops->pluck('sequence', 'id');
        $load = $this->seats->maxLoad($stops->count(), Booking::query()->where('movement_trip_id', $trip->id)->counting()->get()
            ->map(fn (Booking $b) => ['pickup' => $sequence[$b->pickup_stop_id], 'dropoff' => $sequence[$b->dropoff_stop_id], 'pax' => $b->passenger_count]));
        if ($seats < $load) {
            throw new IllegalMovementTransition(__('movement.trips.errors.seats_below_load', ['load' => $load]));
        }
    }

    /** @param callable(CarbonImmutable, CarbonImmutable): array $conflicts */
    private function assertResourceFree(Trip $trip, ?Trip $pair, string $resource, callable $conflicts): void
    {
        if (! $trip->hasWindow()) {
            return; // no timetable yet: checked again when the trip is scheduled (§13.5)
        }

        [$from, $until] = $this->availability->resourceWindow($trip, $pair, $resource);
        $found = $conflicts($from, $until);
        if ($found !== []) {
            throw new IllegalMovementTransition(implode(' ', array_column($found, 'message')));
        }
    }

    private function assertEditable(Trip $trip): void
    {
        if (! in_array($trip->status, [Trip::STATUS_DRAFT, Trip::STATUS_SCHEDULED], true)) {
            throw new IllegalMovementTransition(__('movement.trips.errors.not_editable'));
        }
    }

    private function guardTransition(Trip $trip, string $to): void
    {
        if (! $trip->canTransitionTo($to)) {
            throw new IllegalMovementTransition(__('movement.trips.errors.illegal_from', [
                'from' => __('movement.trip_statuses.'.$trip->status),
                'to' => __('movement.trip_statuses.'.$to),
            ]));
        }
    }

    private function assertValid(Trip $trip): void
    {
        $conflicts = $this->availability->conflictsFor($trip->fresh());
        if ($conflicts !== []) {
            throw new IllegalMovementTransition(implode(' ', array_column($conflicts, 'message')));
        }
    }

    /** §15.3: an edit of a scheduled trip must leave it schedulable. */
    private function assertStillValidIfScheduled(Trip $trip): void
    {
        if ($trip->fresh()->status === Trip::STATUS_SCHEDULED) {
            $this->assertValid($trip);
        }
    }

    private function clearPairing(Trip $trip, Trip $pair, User $actor): void
    {
        $flags = ['paired_trip_id' => null, 'vehicle_waits_between' => false, 'driver_waits_between' => false];
        foreach ([$trip, $pair] as $leg) {
            $old = $leg->paired_trip_id;
            $leg->update($flags);
            MovementAudit::record('trip.unpaired', $leg, ['paired_trip_id' => $old], ['paired_trip_id' => null], $actor->id);
        }
    }

    private function auditAssignment(Trip $trip, array $before, User $actor): void
    {
        foreach (['vehicle', 'driver'] as $resource) {
            $old = $before[$resource.'_id'];
            $new = $trip->{$resource.'_id'};
            if ((int) $old === (int) $new) {
                continue;
            }
            $event = $new === null ? 'removed' : ($old === null ? 'assigned' : 'reassigned');
            MovementAudit::record("trip.{$resource}_{$event}", $trip, [$resource.'_id' => $old], [$resource.'_id' => $new], $actor->id);
        }
        if ((int) $before['seat_capacity'] !== (int) $trip->seat_capacity) {
            MovementAudit::record('trip.seat_capacity_changed', $trip, ['seat_capacity' => $before['seat_capacity']], ['seat_capacity' => $trip->seat_capacity], $actor->id);
        }
    }

    private function stopOf(Trip $trip, int $stopId): TripStop
    {
        return TripStop::query()->where('movement_trip_id', $trip->id)->find($stopId)
            ?? throw new IllegalMovementTransition(__('movement.trips.errors.not_found'));
    }

    private function insertStop(Trip $trip, array $attributes, int $position): TripStop
    {
        $ids = $trip->stops()->pluck('id')->all();
        $this->renumber($trip, $ids, $position); // leave a gap at $position

        return TripStop::create($attributes + ['movement_trip_id' => $trip->id, 'sequence' => $position]);
    }

    /**
     * Give the listed stops sequences 1..n in order, optionally leaving $gap free. Two passes keep the
     * (trip, sequence) unique index satisfied at every step.
     */
    private function renumber(Trip $trip, array $orderedIds, ?int $gap = null): void
    {
        TripStop::query()->where('movement_trip_id', $trip->id)->update(['sequence' => DB::raw('sequence + 10000')]);
        $sequence = 1;
        foreach ($orderedIds as $id) {
            if ($sequence === $gap) {
                $sequence++;
            }
            TripStop::query()->whereKey($id)->update(['sequence' => $sequence++]);
        }
    }

    /** Recompute planned times from the current first departure after a stop change; clear them if a section has no running time. */
    private function retime(Trip $trip): void
    {
        $stops = $trip->stops()->get();
        $departure = $trip->planned_departure_at;
        if ($stops->count() < 2 || ! $departure || $stops->slice(1)->contains(fn (TripStop $s) => $s->running_seconds_from_previous === null)) {
            $this->clearTimetable($trip);

            return;
        }

        $dwells = $this->dwellSeconds($stops, []);
        $this->applyTimes($trip, $stops, $dwells, $this->arrivalOffsets($stops, $dwells), CarbonImmutable::instance($departure));
    }

    private function clearTimetable(Trip $trip): void
    {
        TripStop::query()->where('movement_trip_id', $trip->id)->update(['planned_arrival_at' => null, 'planned_departure_at' => null]);
        $trip->update(['planned_departure_at' => null, 'planned_arrival_at' => null, 'blocked_from' => null, 'blocked_until' => null, 'estimated_duration_s' => null]);
    }

    /** @param array<int, int> $dwells stop id → seconds */
    private function applyTimes(Trip $trip, Collection $stops, array $dwells, array $offsets, CarbonImmutable $departure): void
    {
        $last = $stops->count() - 1;
        $arrival = null;
        foreach ($stops->values() as $i => $stop) {
            $arrival = $i === 0 ? null : $departure->addSeconds($offsets[$stop->id]);
            $leave = $i === 0 ? $departure : ($i === $last ? null : $arrival->addSeconds($dwells[$stop->id] ?? 0));
            $stop->forceFill(['planned_arrival_at' => $arrival, 'planned_departure_at' => $leave])->save();
        }

        $distance = $stops->slice(1)->contains(fn (TripStop $s) => $s->distance_m_from_previous === null) ? null : (int) $stops->sum('distance_m_from_previous');
        $trip->update([
            'planned_departure_at' => $departure,
            'planned_arrival_at' => $arrival,
            'blocked_from' => $departure->subMinutes($trip->pre_trip_buffer_minutes),
            'blocked_until' => $arrival->addMinutes($trip->post_trip_buffer_minutes),
            'estimated_duration_s' => $arrival->diffInSeconds($departure),
            'estimated_distance_m' => $distance,
        ]);
    }

    /**
     * Arrival offset of every stop from the first departure (§9.4): Σ running × (1 + excess %), each section rounded to
     * whole minutes, plus the dwell of every intermediate stop before it.
     *
     * @return array<int, int> stop id → seconds (the first stop is 0)
     */
    private function arrivalOffsets(Collection $stops, array $dwells): array
    {
        $excess = (int) config('movement.buffers.excess_running_percent');
        $offsets = [];
        $elapsed = 0;
        foreach ($stops->values() as $i => $stop) {
            if ($i > 0) {
                $elapsed += (int) round($stop->running_seconds_from_previous / 60 * (100 + $excess) / 100) * 60;
            }
            $offsets[$stop->id] = $elapsed;
            if ($i > 0) {
                $elapsed += $dwells[$stop->id] ?? 0;
            }
        }

        return $offsets;
    }

    /** Dwell per intermediate stop: the given minutes, else the current planned dwell, else the configured default. */
    private function dwellSeconds(Collection $stops, array $dwellMinutes): array
    {
        $default = (int) config('movement.buffers.default_dwell_minutes') * 60;
        $last = $stops->count() - 1;
        $dwells = [];
        foreach ($stops->values() as $i => $stop) {
            if ($i === 0 || $i === $last) {
                continue;
            }
            if (isset($dwellMinutes[$stop->id])) {
                $dwells[$stop->id] = max(0, (int) $dwellMinutes[$stop->id]) * 60;
            } elseif ($stop->planned_arrival_at && $stop->planned_departure_at) {
                $dwells[$stop->id] = $stop->planned_departure_at->diffInSeconds($stop->planned_arrival_at);
            } else {
                $dwells[$stop->id] = $default;
            }
        }

        return $dwells;
    }

    /** min over request-booking deadlines of (deadline − offset(drop-off)), rounded down to 5 minutes (§9.4). */
    private function latestDepartureFor(Trip $trip, Collection $stops, array $offsets): ?CarbonImmutable
    {
        $latest = null;
        $bookings = Booking::query()->where('movement_trip_id', $trip->id)->counting()->whereNotNull('request_leg_id')->with('requestLeg')->get();
        foreach ($bookings as $booking) {
            $deadline = $booking->requestLeg?->required_arrival_at;
            if ($deadline && isset($offsets[$booking->dropoff_stop_id])) {
                $candidate = CarbonImmutable::instance($deadline)->setTimezone('UTC')->subSeconds($offsets[$booking->dropoff_stop_id]);
                $latest = $latest === null ? $candidate : $latest->min($candidate);
            }
        }

        return $latest ? $this->floorToFiveMinutes($latest) : null;
    }

    private function floorToFiveMinutes(CarbonInterface $moment): CarbonImmutable
    {
        $timestamp = $moment->getTimestamp();

        return CarbonImmutable::createFromTimestampUTC($timestamp - ($timestamp % 300));
    }

    private function minutesToSeconds(mixed $minutes): ?int
    {
        return $minutes === null || $minutes === '' ? null : max(0, (int) $minutes) * 60;
    }

    private function timesForAudit(array $values): array
    {
        return array_map(fn ($v) => $v ? CarbonImmutable::instance($v)->format('Y-m-d H:i:s') : null, $values);
    }

    /** Snapshot of a saved, active location (§7.9). */
    private function placeFromLocation(int $locationId): array
    {
        $location = Location::query()->where('is_active', true)->find($locationId)
            ?? throw new IllegalMovementTransition(__('movement.trips.errors.location_inactive'));

        return [
            'location_id' => $location->id,
            'name' => $location->display_name,
            'address' => $location->address,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'maps_url' => $location->maps_url,
        ];
    }

    private function customPlace(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new IllegalMovementTransition(__('movement.trips.errors.stop_name_required'));
        }
        $url = ($data['maps_url'] ?? null) ?: null;
        [$lat, $lng] = Location::coordinatesFromMapsUrl($url) ?? [null, null];

        return ['location_id' => null, 'name' => $name, 'address' => $data['address'] ?? null, 'latitude' => $lat, 'longitude' => $lng, 'maps_url' => $url];
    }

    /** @param 'origin'|'destination' $end */
    private function placeFromLeg(MovementRequestLeg $leg, string $end): array
    {
        $locationId = $leg->{$end.'_location_id'};
        if ($locationId) {
            $location = Location::withTrashed()->find($locationId);

            return [
                'location_id' => $location->id,
                'name' => $location->display_name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'maps_url' => $location->maps_url,
            ];
        }

        return $this->customPlace(['name' => $leg->{$end.'_text'}, 'maps_url' => $leg->{$end.'_maps_url'}]);
    }
}
