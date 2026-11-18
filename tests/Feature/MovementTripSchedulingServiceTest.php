<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\Booking;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Services\MovementAvailabilityService;
use App\Modules\Movement\Services\MovementRequestWorkflow;
use Database\Seeders\MovementPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\MovementTripFixtures;
use Tests\TestCase;

/**
 * TripSchedulingService, MovementAvailabilityService (trip side) and the Phase 5 MovementRequestWorkflow changes
 * (plan §8.4–8.5, §9.4, §11.3–11.6, §13.1–13.7, §15.3; Revision 6).
 * Database test — run only against a disposable database (CLAUDE.md, database safety).
 */
class MovementTripSchedulingServiceTest extends TestCase
{
    use RefreshDatabase;
    use MovementTripFixtures;

    private function refused(callable $action, ?string $expectedMessage = null): IllegalMovementTransition
    {
        try {
            $action();
        } catch (IllegalMovementTransition $e) {
            if ($expectedMessage !== null) {
                $this->assertStringContainsString($expectedMessage, $e->getMessage());
            }

            return $e;
        }

        $this->fail('The action should have been refused.');
    }

    private function codes(Trip $trip): array
    {
        return array_column(app(MovementAvailabilityService::class)->conflictsFor($trip->fresh()), 'code');
    }

    private function allocate($leg, Trip $trip, int $pickup, int $dropoff, int $pax): Booking
    {
        return $this->trips()->allocateRequestLeg($leg, $trip, $this->stopAt($trip, $pickup), $this->stopAt($trip, $dropoff), $pax, $this->actor());
    }

    private function fulfilment($leg): MovementRequest
    {
        return MovementRequest::query()->findOrFail($leg->movement_request_id);
    }

    // ------------------------------------------------------------------ creation

    public function test_draft_from_an_approved_leg_snapshots_its_places(): void
    {
        $leg = $this->approvedLeg(12, '2030-01-15 10:00', 'Khalda', 'Irbid');
        $trip = $this->trips()->createDraft($this->actor(), $leg);

        $this->assertSame(Trip::STATUS_DRAFT, $trip->status);
        $this->assertSame(sprintf('MT-%s-%06d', now('Asia/Amman')->format('Y'), $trip->id), $trip->reference_no, 'Revision 6: MT-{local year}-{id}.');
        $this->assertSame([15, 15], [$trip->pre_trip_buffer_minutes, $trip->post_trip_buffer_minutes], 'D4 buffers.');
        $this->assertNull($trip->seat_capacity, 'Revision 6: empty until set or assigned.');

        $stops = $trip->stops()->get();
        $this->assertSame(['Khalda', 'Irbid'], $stops->pluck('name')->all());
        $this->assertSame([[true, false], [false, true]], $stops->map(fn ($s) => [$s->allows_pickup, $s->allows_drop_off])->all());
        $this->assertSame(1, AuditLog::query()->where('action', 'trip.created')->where('entity_id', $trip->id)->count());
    }

    public function test_draft_refuses_a_leg_whose_request_is_not_approved(): void
    {
        $leg = $this->approvedLeg(5, '2030-01-15 10:00', status: MovementRequest::STATUS_SUBMITTED);

        $this->refused(fn () => $this->trips()->createDraft($this->actor(), $leg), __('movement.trips.errors.request_not_approved'));
        $this->assertSame(0, Trip::query()->count());
    }

    // ------------------------------------------------------------------ timetable (§9.4)

    public function test_section_9_4_worked_example(): void
    {
        $trip = $this->tripWithStops(['Amman', 'Salt', 'Jerash', 'Irbid'], 30);
        $this->allocate($this->approvedLeg(15, '2030-01-15 10:00', 'Amman', 'Irbid'), $trip, 1, 4, 15);
        $this->allocate($this->approvedLeg(10, '2030-01-15 08:15', 'Amman', 'Salt'), $trip, 1, 2, 10);
        $stops = $trip->stops()->get();
        $running = [$stops[1]->id => 35, $stops[2]->id => 50, $stops[3]->id => 40];

        $trip = $this->trips()->setTimetable($trip, $this->actor(), $running, [], ['type' => 'latest']);

        $this->assertSame('2030-01-15 07:25', $this->localTime($trip->planned_departure_at), 'min(07:26, 07:35) rounded down to 5 minutes.');
        $times = $trip->stops()->get()->map(fn ($s) => [$this->localTime($s->planned_arrival_at), $this->localTime($s->planned_departure_at)])->all();
        $this->assertSame([
            [null, '2030-01-15 07:25'],
            ['2030-01-15 08:05', '2030-01-15 08:10'],
            ['2030-01-15 09:08', '2030-01-15 09:13'],
            ['2030-01-15 09:59', null],
        ], $times);
        $this->assertSame(['2030-01-15 07:10', '2030-01-15 10:14'], [$this->localTime($trip->blocked_from), $this->localTime($trip->blocked_until)]);
        $this->assertSame('2030-01-15 07:25', $this->localTime($this->trips()->latestDeparture($trip)));

        // A later departure fails; an earlier one is allowed (§9.4).
        $this->refused(fn () => $this->trips()->setTimetable($trip, $this->actor(), [], [], ['type' => 'depart_at', 'at' => $this->local('2030-01-15 07:30')]), '2030-01-15 07:25');
        $earlier = $this->trips()->setTimetable($trip, $this->actor(), [], [], ['type' => 'depart_at', 'at' => $this->local('2030-01-15 07:00')]);
        $this->assertSame('2030-01-15 09:34', $this->localTime($earlier->planned_arrival_at));

        // "Arrive by" at Irbid 10:00: 10:00 − 154 min = 07:26, rounded down to 07:25.
        $arriveBy = $this->trips()->setTimetable($trip, $this->actor(), [], [], ['type' => 'arrive_by', 'at' => $this->local('2030-01-15 10:00'), 'stop_id' => $stops[3]->id]);
        $this->assertSame('2030-01-15 07:25', $this->localTime($arriveBy->planned_departure_at));
    }

    public function test_midnight_crossing_trip_and_adjacent_windows(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        // 91 free-flow minutes × 1.15 = 104.65 → 105: 23:30 → 01:15 (§9.5 test case).
        $first = $this->scheduledTrip('2030-01-15 23:30', 91, $vehicle, $driver);
        $this->assertSame('2030-01-16 01:15', $this->localTime($first->planned_arrival_at));
        $this->assertSame('2030-01-16 01:30', $this->localTime($first->blocked_until));
        $this->assertTrue($first->planned_arrival_at->gt($first->planned_departure_at));

        // blocked_from 01:15 < 01:30: overlap.
        $overlapping = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-16 01:30', [30]);
        $this->refused(fn () => $this->trips()->assign($overlapping, $this->actor(), $vehicle->id, null), $first->reference_no);

        // blocked_from 01:30 == blocked_until 01:30: not an overlap.
        $adjacent = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-16 01:45', [30]);
        $this->trips()->assign($adjacent, $this->actor(), $vehicle->id, $driver->id);
        $this->assertSame(Trip::STATUS_SCHEDULED, $this->trips()->schedule($adjacent, $this->actor())->status);
    }

    // ------------------------------------------------------------------ allocation (§11.3–11.6)

    public function test_section_11_3_example_through_allocation(): void
    {
        $trip = $this->tripWithStops(['Amman', 'Salt', 'Jerash', 'Irbid'], 30);
        $this->allocate($this->approvedLeg(15, '2030-01-15 12:00'), $trip, 1, 4, 15);
        $this->allocate($this->approvedLeg(10, '2030-01-15 12:00'), $trip, 1, 2, 10);
        $this->allocate($this->approvedLeg(12, '2030-01-15 12:00'), $trip, 2, 4, 12);

        $this->allocate($this->approvedLeg(5, '2030-01-15 12:00'), $trip, 1, 2, 5); // section 1 → 30
        $this->refused(fn () => $this->allocate($this->approvedLeg(1, '2030-01-15 12:00'), $trip, 1, 3, 1), __('movement.trips.errors.no_seats', ['available' => 0]));
        $this->allocate($this->approvedLeg(3, '2030-01-15 12:00'), $trip, 3, 4, 3);

        $this->assertSame(5, Booking::query()->where('movement_trip_id', $trip->id)->count());
        $this->assertNotContains('load_exceeds_capacity', $this->codes($trip));
    }

    public function test_leg_aggregate_across_trips_and_cancelled_trips(): void
    {
        $leg = $this->approvedLeg(45, '2030-01-15 12:00');
        $bus = $this->tripWithStops(['A', 'B'], 30);
        $minibus = $this->tripWithStops(['A', 'B'], 30);
        $spare = $this->tripWithStops(['A', 'B'], 30);

        $this->allocate($leg, $bus, 1, 2, 30);
        $this->refused(fn () => $this->allocate($leg, $minibus, 1, 2, 16), __('movement.trips.errors.leg_over_allocated', ['remaining' => 15]));
        $this->allocate($leg, $minibus, 1, 2, 15);
        $this->assertSame(MovementRequest::FULFILMENT_ALLOCATED, $this->fulfilment($leg)->fulfilment_status);

        // Bookings on cancelled trips no longer count (§11.6): the 15 can be allocated again.
        $this->trips()->cancel($minibus, $this->actor(), 'Vehicle reassigned');
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_ALLOCATED, $this->fulfilment($leg)->fulfilment_status);
        $this->allocate($leg, $spare, 1, 2, 15);
        $this->assertSame(45, (int) Booking::query()->where('request_leg_id', $leg->id)->where('status', Booking::STATUS_CONFIRMED)->sum('passenger_count'));
    }

    public function test_allocation_revalidates_span_request_status_and_capacity(): void
    {
        $trip = $this->tripWithStops(['A', 'B', 'C'], 10);
        $other = $this->tripWithStops(['X', 'Y'], 10);
        $leg = $this->approvedLeg(4, '2030-01-15 12:00');

        $span = __('movement.trips.errors.span_invalid');
        $this->refused(fn () => $this->allocate($leg, $trip, 2, 1, 1), $span);
        $this->refused(fn () => $this->trips()->allocateRequestLeg($leg, $trip, $this->stopAt($other, 1), $this->stopAt($trip, 2), 1, $this->actor()), $span);
        $this->trips()->updateStop($trip, $this->stopAt($trip, 3), $this->actor(), ['allows_drop_off' => false]);
        $this->refused(fn () => $this->allocate($leg, $trip, 1, 3, 1), $span);

        $unapproved = $this->approvedLeg(2, '2030-01-15 12:00', status: MovementRequest::STATUS_UNDER_REVIEW);
        $this->refused(fn () => $this->allocate($unapproved, $trip, 1, 2, 1), __('movement.trips.errors.request_not_approved'));

        $noSeats = $this->tripWithStops(['A', 'B']);
        $this->refused(fn () => $this->allocate($leg, $noSeats, 1, 2, 1), __('movement.trips.errors.capacity_missing'));
        $this->refused(fn () => $this->allocate($leg, $trip, 1, 2, 0));
        $this->assertSame(0, Booking::query()->count());
    }

    public function test_deallocate_and_reallocate(): void
    {
        $leg = $this->approvedLeg(20, '2030-01-15 12:00');
        $first = $this->tripWithStops(['A', 'B'], 20);
        $second = $this->tripWithStops(['A', 'B'], 20);
        $booking = $this->allocate($leg, $first, 1, 2, 20);

        $this->trips()->deallocate($booking, $this->actor(), 5);
        $this->assertSame(15, $booking->fresh()->passenger_count);
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_ALLOCATED, $this->fulfilment($leg)->fulfilment_status);

        $moved = $this->trips()->reallocate($booking, $second, $this->stopAt($second, 1), $this->stopAt($second, 2), 10, $this->actor());
        $this->assertSame([5, 10], [$booking->fresh()->passenger_count, $moved->passenger_count]);
        $this->assertSame($second->id, $moved->movement_trip_id);
        $this->assertSame(1, AuditLog::query()->where('action', 'booking.reallocated')->count());

        $this->refused(fn () => $this->trips()->reallocate($booking, $first, $this->stopAt($first, 1), $this->stopAt($first, 2), 1, $this->actor()), __('movement.trips.errors.same_trip'));
        $this->refused(fn () => $this->trips()->reallocate($booking, $second, $this->stopAt($second, 1), $this->stopAt($second, 2), 6, $this->actor()));

        $this->trips()->deallocate($booking, $this->actor());
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(10, (int) Booking::query()->where('request_leg_id', $leg->id)->where('status', Booking::STATUS_CONFIRMED)->sum('passenger_count'));
        $this->refused(fn () => $this->trips()->deallocate($booking, $this->actor()), __('movement.trips.errors.booking_not_active'));
    }

    public function test_fulfilment_moves_forward_and_regresses(): void
    {
        $leg = $this->approvedLeg(20, '2030-01-15 12:00');
        $first = $this->timed($this->tripWithStops(['A', 'B'], 10), '2030-01-15 08:00', [60]);
        $second = $this->timed($this->tripWithStops(['A', 'B'], 10), '2030-01-15 08:00', [60]);
        $this->trips()->assign($first, $this->actor(), $this->vehicle(10)->id, $this->driver()->id);
        $this->trips()->assign($second, $this->actor(), $this->vehicle(10)->id, $this->driver()->id);
        $status = fn () => $this->fulfilment($leg)->fulfilment_status;

        $this->assertSame(MovementRequest::FULFILMENT_UNALLOCATED, $status());
        $this->allocate($leg, $first, 1, 2, 10);
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_ALLOCATED, $status());
        $this->allocate($leg, $second, 1, 2, 10);
        $this->assertSame(MovementRequest::FULFILMENT_ALLOCATED, $status());
        $this->trips()->schedule($first, $this->actor());
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_SCHEDULED, $status());
        $this->trips()->schedule($second, $this->actor());
        $this->assertSame(MovementRequest::FULFILMENT_SCHEDULED, $status());
        $this->assertNull($this->fulfilment($leg)->fulfilment_dropped_at);

        // Regression: a serving trip returns to draft (§8.5) …
        $this->trips()->unschedule($second, $this->actor());
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_SCHEDULED, $status());
        $this->assertNotNull($this->fulfilment($leg)->fulfilment_dropped_at);
        // … the flag clears when the request is scheduled again …
        $this->trips()->schedule($second, $this->actor());
        $this->assertNull($this->fulfilment($leg)->fulfilment_dropped_at);
        // … and a cancelled serving trip lowers it again.
        $this->trips()->cancel($first, $this->actor(), 'Breakdown');
        $this->assertSame(MovementRequest::FULFILMENT_PARTIALLY_ALLOCATED, $status());
        $this->assertNotNull($this->fulfilment($leg)->fulfilment_dropped_at);
    }

    // ------------------------------------------------------------------ assignment and scheduling (§13.1–13.6)

    public function test_schedule_requires_a_valid_trip(): void
    {
        $trip = $this->tripWithStops(['A', 'B'], 10);
        $this->refused(fn () => $this->trips()->schedule($trip, $this->actor()));
        $this->assertEqualsCanonicalizing(['times_missing', 'vehicle_missing', 'driver_missing'], $this->codes($trip));

        $this->timed($trip, '2030-01-15 08:00', [60]);
        $this->trips()->assign($trip, $this->actor(), $this->vehicle(10)->id, $this->driver()->id);
        $this->assertSame([], $this->codes($trip));
        $this->assertSame(Trip::STATUS_SCHEDULED, $this->trips()->schedule($trip, $this->actor())->status);
        $this->assertSame(1, AuditLog::query()->where('action', 'trip.scheduled')->count());
    }

    public function test_overlapping_assignment_is_refused_and_drafts_do_not_block(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $first = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver); // blocked 07:45 – 09:24

        $overlap = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 09:00', [30]);
        $this->refused(fn () => $this->trips()->assign($overlap, $this->actor(), $vehicle->id, null), $first->reference_no);
        $this->refused(fn () => $this->trips()->assign($overlap, $this->actor(), $this->vehicle()->id, $driver->id), $first->reference_no);

        // Draft trips block nothing: two drafts may hold the same vehicle; the second cannot be scheduled.
        $draftA = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 13:00', [30]);
        $draftB = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 13:00', [30]);
        $this->trips()->assign($draftA, $this->actor(), $vehicle->id, $driver->id);
        $this->trips()->assign($draftB, $this->actor(), $vehicle->id, $this->driver()->id);
        $this->trips()->schedule($draftA, $this->actor());
        $this->refused(fn () => $this->trips()->schedule($draftB, $this->actor()), $draftA->reference_no);
        $this->assertContains('vehicle_busy', $this->codes($draftB));
    }

    public function test_unavailable_resources_are_never_offered(): void
    {
        $from = $this->local('2030-01-15 08:00');
        $until = $this->local('2030-01-15 10:00');
        $windowed = $this->vehicle();
        Unavailability::create(['vehicle_id' => $windowed->id, 'type' => 'maintenance', 'starts_at' => $this->local('2030-01-15 09:00'), 'ends_at' => $this->local('2030-01-15 12:00')]);
        $this->vehicle(30, ['operational_status' => Vehicle::STATUS_MAINTENANCE]);
        Vehicle::create(['fleet_number' => 'IMPORTED', 'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION]);
        $busy = $this->vehicle();
        $this->scheduledTrip('2030-01-15 08:30', 30, $busy, $this->driver());
        $free = $this->vehicle();
        $small = $this->vehicle(8);

        $service = app(MovementAvailabilityService::class);
        $this->assertSame([$free->id, $small->id], $service->freeVehicles($from, $until)->pluck('id')->sort()->values()->all());
        $this->assertSame([$free->id], $service->freeVehicles($from, $until, 20)->pluck('id')->all());

        $onLeave = $this->driver();
        Unavailability::create(['driver_id' => $onLeave->id, 'type' => 'leave', 'starts_at' => $this->local('2030-01-14 00:00'), 'ends_at' => $this->local('2030-01-16 00:00')]);
        $this->driver(['employment_status' => Driver::STATUS_INACTIVE]);
        $this->driver(['licence_expires_on' => '2030-01-14']);
        $ok = $this->driver();
        $freeDrivers = $service->freeDrivers($from, $until)->pluck('id')->all();
        $this->assertContains($ok->id, $freeDrivers);
        $this->assertNotContains($onLeave->id, $freeDrivers);
        $this->assertCount(1, $freeDrivers, 'Only the driver free and licensed in the window.');

        $trip = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 08:00', [60]);
        $this->refused(fn () => $this->trips()->assign($trip, $this->actor(), $windowed->id, null));
        $this->refused(fn () => $this->trips()->assign($trip, $this->actor(), null, $onLeave->id));
        $this->assertNull($trip->fresh()->vehicle_id);
    }

    public function test_seat_capacity_rules(): void
    {
        $trip = $this->tripWithStops(['A', 'B']);
        $this->trips()->setSeatCapacity($trip, $this->actor(), 50); // no vehicle yet (Revision 6)
        $trip = $this->trips()->assign($trip, $this->actor(), $this->vehicle(20)->id, null);
        $this->assertSame(20, $trip->seat_capacity, 'min(previous, vehicle capacity).');

        $this->refused(fn () => $this->trips()->setSeatCapacity($trip, $this->actor(), 25), __('movement.trips.errors.seats_over_vehicle', ['capacity' => 20]));
        $this->allocate($this->approvedLeg(12, '2030-01-15 12:00'), $trip, 1, 2, 12);
        $this->refused(fn () => $this->trips()->setSeatCapacity($trip, $this->actor(), 10), __('movement.trips.errors.seats_below_load', ['load' => 12]));
        $this->trips()->setSeatCapacity($trip, $this->actor(), 15);

        $this->assertSame(14, $this->trips()->assign($trip, $this->actor(), $this->vehicle(14)->id, null)->seat_capacity);
        $this->refused(fn () => $this->trips()->assign($trip, $this->actor(), $this->vehicle(10)->id, null), __('movement.trips.errors.seats_below_load', ['load' => 12]));

        $fresh = $this->trips()->assign($this->tripWithStops(['A', 'B']), $this->actor(), $this->vehicle(33)->id, null);
        $this->assertSame(33, $fresh->seat_capacity, 'The first assignment fills seats from the vehicle.');
    }

    public function test_removing_the_driver_returns_a_scheduled_trip_to_draft(): void
    {
        $leg = $this->approvedLeg(10, '2030-01-15 12:00');
        $vehicle = $this->vehicle(10);
        $trip = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $this->driver());
        $this->allocate($leg, $trip, 1, 2, 10);
        $this->assertSame(MovementRequest::FULFILMENT_SCHEDULED, $this->fulfilment($leg)->fulfilment_status);

        $trip = $this->trips()->assign($trip, $this->actor(), $vehicle->id, null);
        $this->assertSame(Trip::STATUS_DRAFT, $trip->status);
        $this->assertSame(MovementRequest::FULFILMENT_ALLOCATED, $this->fulfilment($leg)->fulfilment_status);
        $this->assertSame(1, AuditLog::query()->where('action', 'trip.driver_removed')->count());
    }

    // ------------------------------------------------------------------ licences (§13.2, D8, Revision 6)

    public function test_licence_boundary_rule(): void
    {
        $valid = fn (?string $expires, string $untilLocal) => MovementAvailabilityService::licenceValidFor(new Driver(['licence_expires_on' => $expires]), $this->local($untilLocal));

        $this->assertTrue($valid('2030-01-15', '2030-01-15 18:00'), 'Expiring today: valid through the whole local day.');
        $this->assertFalse($valid('2030-01-14', '2030-01-15 08:00'));
        $this->assertTrue($valid('2030-01-15', '2030-01-16 00:00'), 'A window ending exactly at local midnight needs the licence only through the previous day.');
        $this->assertFalse($valid('2030-01-15', '2030-01-16 00:01'));
        $this->assertTrue($valid(null, '2030-01-15 08:00'), 'No expiry date: not tracked, assignable.');
    }

    public function test_licence_is_checked_at_assignment_without_deactivating_the_driver(): void
    {
        $trip = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 08:00', [60]);
        $expired = $this->driver(['licence_expires_on' => '2030-01-14']);

        $this->refused(fn () => $this->trips()->assign($trip, $this->actor(), null, $expired->id), '2030-01-14');
        $this->assertSame(Driver::STATUS_ACTIVE, $expired->fresh()->employment_status, 'The driver stays active.');
        $this->assertSame(Driver::LICENCE_EXPIRED, $expired->fresh()->licenceState(\Carbon\CarbonImmutable::parse('2030-01-15', 'Asia/Amman')), 'And is flagged for the manager (Phase 3) on the trip day.');

        $this->trips()->assign($trip, $this->actor(), null, $this->driver(['licence_expires_on' => '2030-01-15'])->id);
        $this->trips()->assign($trip, $this->actor(), null, $this->driver()->id);
        $this->assertNotNull($trip->fresh()->driver_id);
    }

    // ------------------------------------------------------------------ Revision 6 (b): flagged, not blocked

    public function test_windows_and_licence_expiry_after_scheduling_are_flagged_and_block_rescheduling(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $trip = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver);

        $leave = Unavailability::create(['driver_id' => $driver->id, 'type' => 'leave', 'starts_at' => $this->local('2030-01-15 09:00'), 'ends_at' => $this->local('2030-01-15 18:00')]);
        $maintenance = Unavailability::create(['vehicle_id' => $vehicle->id, 'type' => 'maintenance', 'starts_at' => $this->local('2030-01-15 07:00'), 'ends_at' => $this->local('2030-01-15 08:00')]);
        $this->assertSame(Trip::STATUS_SCHEDULED, $trip->fresh()->status, 'The scheduled trip stays scheduled.');
        $this->assertEqualsCanonicalizing(['driver_unavailable', 'vehicle_unavailable'], $this->codes($trip));

        $this->trips()->unschedule($trip, $this->actor());
        $this->refused(fn () => $this->trips()->schedule($trip, $this->actor()));
        $leave->delete();
        $maintenance->delete();

        $driver->update(['licence_expires_on' => '2030-01-14']);
        $this->assertSame(['driver_licence_expired'], $this->codes($trip));
        $this->refused(fn () => $this->trips()->schedule($trip, $this->actor()), '2030-01-14');

        $driver->update(['licence_expires_on' => null]);
        $this->assertSame(Trip::STATUS_SCHEDULED, $this->trips()->schedule($trip, $this->actor())->status);
    }

    public function test_edits_that_resolve_a_flagged_conflict_are_allowed_and_others_refused(): void
    {
        $vehicle = $this->vehicle(20);
        $driver = $this->driver();
        $trip = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver); // blocked 07:45 – 09:24
        Unavailability::create(['driver_id' => $driver->id, 'type' => 'leave', 'starts_at' => $this->local('2030-01-15 09:00'), 'ends_at' => $this->local('2030-01-15 18:00')]);
        $this->assertSame(['driver_unavailable'], $this->codes($trip));

        // An edit that leaves the conflict in place is refused, and nothing changes.
        $this->refused(fn () => $this->trips()->setSeatCapacity($trip, $this->actor(), 15));
        $this->refused(fn () => $this->trips()->setTimetable($trip, $this->actor(), [], [], ['type' => 'depart_at', 'at' => $this->local('2030-01-15 08:10')]));
        $this->assertSame([20, '2030-01-15 08:00'], [$trip->fresh()->seat_capacity, $this->localTime($trip->fresh()->planned_departure_at)]);

        // Replacing the conflicting driver resolves it: allowed, and the trip stays scheduled.
        $trip = $this->trips()->assign($trip, $this->actor(), $vehicle->id, $this->driver()->id);
        $this->assertSame([Trip::STATUS_SCHEDULED, []], [$trip->status, $this->codes($trip)]);

        // Moving the times out of a maintenance window also resolves it.
        Unavailability::create(['vehicle_id' => $vehicle->id, 'type' => 'maintenance', 'starts_at' => $this->local('2030-01-15 07:00'), 'ends_at' => $this->local('2030-01-15 09:00')]);
        $this->assertSame(['vehicle_unavailable'], $this->codes($trip));
        $trip = $this->trips()->setTimetable($trip, $this->actor(), [], [], ['type' => 'depart_at', 'at' => $this->local('2030-01-15 09:30')]);
        $this->assertSame([Trip::STATUS_SCHEDULED, '2030-01-15 09:30', []], [$trip->status, $this->localTime($trip->planned_departure_at), $this->codes($trip)]);
    }

    public function test_conflicts_mark_availability_as_not_checked_before_timing(): void
    {
        $trip = $this->tripWithStops(['A', 'B'], 10);
        $this->assertNotContains('availability_not_checked', $this->codes($trip), 'Nothing assigned: nothing to check.');

        // Assignment before timing is allowed (Revision 6); a vehicle in maintenance is not detected yet …
        $trip = $this->trips()->assign($trip, $this->actor(), $this->vehicle(10)->id, $this->driver()->id);
        Vehicle::query()->whereKey($trip->vehicle_id)->update(['operational_status' => Vehicle::STATUS_MAINTENANCE]);
        $this->assertEqualsCanonicalizing(['times_missing', 'availability_not_checked'], $this->codes($trip));

        // … and is reported as soon as the trip has times.
        $this->timed($trip, '2030-01-15 08:00', [60]);
        $this->assertSame(['vehicle_not_operational'], $this->codes($trip));
    }

    // ------------------------------------------------------------------ pairing (§13.7, D22)

    public function test_pairing_reserves_the_span_and_the_driver_flag_follows_the_vehicle_flag(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $outbound = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver);
        $return = $this->scheduledTrip('2030-01-15 14:00', 60, $vehicle, $driver, Trip::STATUS_DRAFT);

        $this->trips()->pair($outbound, $return, $this->actor(), true);
        $outbound->refresh();
        $this->assertSame([$return->id, true, true], [$outbound->paired_trip_id, $outbound->vehicle_waits_between, $outbound->driver_waits_between], 'D22: the driver waits too by default.');
        $this->assertSame($outbound->id, $return->fresh()->paired_trip_id);

        // 11:00 lies between the legs: both resources are reserved.
        $between = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 11:00', [30]);
        $this->refused(fn () => $this->trips()->assign($between, $this->actor(), $vehicle->id, null), $outbound->reference_no);
        $this->refused(fn () => $this->trips()->assign($between, $this->actor(), null, $driver->id), $outbound->reference_no);

        // A reassigned waiting resource moves to both legs in the same transaction.
        $spare = $this->vehicle();
        $this->trips()->assign($return, $this->actor(), $spare->id, $driver->id);
        $this->assertSame($spare->id, $outbound->fresh()->vehicle_id);
        $this->trips()->assign($return, $this->actor(), $vehicle->id, $driver->id);

        // A pair of draft trips reserves nothing (§13.1).
        $this->trips()->unschedule($outbound, $this->actor());
        $this->trips()->assign($between, $this->actor(), $vehicle->id, $driver->id);
        $this->assertSame($vehicle->id, $between->fresh()->vehicle_id);
    }

    public function test_driver_may_be_released_between_paired_trips(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $outbound = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver);
        $return = $this->scheduledTrip('2030-01-15 14:00', 60, $vehicle, $driver, Trip::STATUS_DRAFT);
        $this->trips()->pair($outbound, $return, $this->actor(), true, false);

        $between = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 11:00', [30]);
        $this->trips()->assign($between, $this->actor(), $this->vehicle()->id, $driver->id);
        $this->assertSame($driver->id, $between->fresh()->driver_id);
    }

    public function test_pairing_rules_and_cancelling_one_leg_clears_the_pair(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $outbound = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver);
        $return = $this->scheduledTrip('2030-01-15 14:00', 60, $vehicle, $driver, Trip::STATUS_DRAFT);
        $other = $this->scheduledTrip('2030-01-16 14:00', 60, $this->vehicle(), $driver, Trip::STATUS_DRAFT);

        $this->refused(fn () => $this->trips()->pair($outbound, $other, $this->actor(), true), __('movement.trips.errors.pair_resources_differ'));
        $this->trips()->pair($outbound, $return, $this->actor(), true);
        $this->refused(fn () => $this->trips()->pair($other, $return, $this->actor(), false), $return->reference_no);

        $this->trips()->cancel($return, $this->actor(), 'Group returns by itself');
        $this->assertNull($outbound->fresh()->paired_trip_id);
        $this->assertNull($return->fresh()->paired_trip_id);
        $this->assertFalse($outbound->fresh()->vehicle_waits_between);
    }

    // ------------------------------------------------------------------ cancel and transitions (§15.3)

    public function test_cancel_needs_a_reason_and_releases_bookings(): void
    {
        $leg = $this->approvedLeg(6, '2030-01-15 12:00');
        $trip = $this->tripWithStops(['A', 'B'], 6);
        $booking = $this->allocate($leg, $trip, 1, 2, 6);

        $this->refused(fn () => $this->trips()->cancel($trip, $this->actor(), '  '), __('movement.trips.errors.reason_required'));
        $this->trips()->cancel($trip, $this->actor(), 'Event postponed');

        $trip->refresh();
        $this->assertSame([Trip::STATUS_CANCELLED, 'Event postponed', $this->actor()->id], [$trip->status, $trip->cancellation_reason, $trip->cancelled_by]);
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(MovementRequest::FULFILMENT_UNALLOCATED, $this->fulfilment($leg)->fulfilment_status);
    }

    public function test_only_phase_5_transitions_are_allowed(): void
    {
        $draft = $this->tripWithStops(['A', 'B'], 5);
        $this->refused(fn () => $this->trips()->unschedule($draft, $this->actor()));

        $this->trips()->cancel($draft, $this->actor(), 'Not needed');
        $this->refused(fn () => $this->trips()->schedule($draft, $this->actor()));
        $this->refused(fn () => $this->trips()->cancel($draft, $this->actor(), 'Again'));
        $this->refused(fn () => $this->trips()->addStop($draft, $this->actor(), ['name' => 'C']), __('movement.trips.errors.not_editable'));

        $this->assertSame([Trip::STATUS_DRAFT => [Trip::STATUS_SCHEDULED, Trip::STATUS_CANCELLED], Trip::STATUS_SCHEDULED => [Trip::STATUS_DRAFT, Trip::STATUS_CANCELLED]], Trip::TRANSITIONS);
    }

    // ------------------------------------------------------------------ stops (§7.9, §11.4)

    public function test_stop_insert_remove_reorder_and_flag_rules(): void
    {
        $trip = $this->tripWithStops(['A', 'B', 'C', 'D'], 10);
        $booking = $this->allocate($this->approvedLeg(2, '2030-01-15 12:00'), $trip, 1, 4, 2);
        [$a, $b, $c, $d] = $trip->stops()->get()->all();

        $x = $this->trips()->addStop($trip, $this->actor(), ['name' => 'X'], 2);
        $this->assertSame(['A', 'X', 'B', 'C', 'D'], $trip->stops()->pluck('name')->all(), 'Inserting keeps the relative order.');
        $this->assertSame([$a->id, $d->id], [$booking->fresh()->pickup_stop_id, $booking->fresh()->dropoff_stop_id]);

        $this->refused(fn () => $this->trips()->removeStop($trip, $a, $this->actor()), __('movement.trips.errors.stop_referenced'));
        $this->trips()->removeStop($trip, $x, $this->actor());
        $this->assertSame([1, 2, 3, 4], $trip->stops()->pluck('sequence')->all());

        $this->refused(fn () => $this->trips()->reorderStops($trip, $this->actor(), [$b->id, $a->id, $c->id, $d->id]), __('movement.trips.errors.stop_referenced'));
        $this->trips()->reorderStops($trip, $this->actor(), [$a->id, $c->id, $b->id, $d->id]);
        $this->assertSame(['A', 'C', 'B', 'D'], $trip->stops()->pluck('name')->all(), 'Unreferenced stops may move.');

        $this->refused(fn () => $this->trips()->updateStop($trip, $a, $this->actor(), ['allows_pickup' => false]), __('movement.trips.errors.stop_flag_in_use'));
        $this->refused(fn () => $this->trips()->updateStop($trip, $d, $this->actor(), ['allows_drop_off' => false]), __('movement.trips.errors.stop_flag_in_use'));
        $this->trips()->updateStop($trip, $b, $this->actor(), ['allows_pickup' => false]);
        $this->assertFalse($b->fresh()->allows_pickup);
    }

    public function test_editing_a_scheduled_trip_must_keep_it_valid(): void
    {
        $vehicle = $this->vehicle();
        $driver = $this->driver();
        $early = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $driver); // blocked 07:45 – 09:24
        $late = $this->scheduledTrip('2030-01-15 10:00', 60, $vehicle, $driver); // blocked 09:45 – 11:24

        // Moving the early trip 30 minutes later would overlap the late one.
        $this->refused(fn () => $this->trips()->setTimetable($early, $this->actor(), [], [], ['type' => 'depart_at', 'at' => $this->local('2030-01-15 08:30')]), $late->reference_no);
        $this->assertSame('2030-01-15 08:00', $this->localTime($early->fresh()->planned_departure_at), 'Nothing changed.');

        // A stop without a running time leaves the timetable incomplete: refused on a scheduled trip.
        $this->refused(fn () => $this->trips()->addStop($early, $this->actor(), ['name' => 'Extra']));
        $this->assertSame(2, $early->stops()->count());

        // With its running times the stop fits.
        $this->trips()->addStop($early, $this->actor(), ['name' => 'Via', 'running_minutes' => 10, 'next_running_minutes' => 10], 2);
        $this->assertSame(3, $early->stops()->count());
        $this->assertSame(Trip::STATUS_SCHEDULED, $early->fresh()->status);
    }

    // ------------------------------------------------------------------ request cancellation (§8.4, Revision 5)

    public function test_cancelling_an_approved_request_cancels_its_bookings_and_releases_seats(): void
    {
        $this->seed(MovementPermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $leg = $this->approvedLeg(10, '2030-01-15 12:00');
        $trip = $this->tripWithStops(['A', 'B'], 10);
        $booking = $this->allocate($leg, $trip, 1, 2, 10);

        app(MovementRequestWorkflow::class)->cancel($this->fulfilment($leg), $this->actor(), 'Event cancelled');

        $request = $this->fulfilment($leg);
        $this->assertSame([MovementRequest::STATUS_CANCELLED, MovementRequest::FULFILMENT_CANCELLED], [$request->status, $request->fulfilment_status]);
        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->allocate($this->approvedLeg(10, '2030-01-15 12:00'), $trip, 1, 2, 10); // the seats are free again
    }

    public function test_request_cancel_is_refused_while_a_serving_trip_is_in_progress(): void
    {
        $this->seed(MovementPermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $leg = $this->approvedLeg(10, '2030-01-15 12:00');
        $trip = $this->tripWithStops(['A', 'B'], 10);
        $booking = $this->allocate($leg, $trip, 1, 2, 10);
        Trip::query()->whereKey($trip->id)->update(['status' => Trip::STATUS_IN_PROGRESS]); // execution arrives in Phase 10

        $this->refused(fn () => app(MovementRequestWorkflow::class)->cancel($this->fulfilment($leg), $this->actor(), 'Too late'), __('movement.requests.errors.serving_trip_in_progress'));
        $this->assertSame(MovementRequest::STATUS_APPROVED, $this->fulfilment($leg)->status);
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }
}
