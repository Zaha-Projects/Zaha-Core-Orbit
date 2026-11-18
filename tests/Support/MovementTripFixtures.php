<?php

namespace Tests\Support;

use App\Models\User;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\TripStop;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Services\TripSchedulingService;
use App\Modules\Movement\Support\MovementTime;
use Carbon\CarbonImmutable;

/**
 * Inline test data for Movement Phase 5 tests (plan §5.2: no demo seeder; tests build their own data).
 * Times passed as strings are local Asia/Amman wall-clock values.
 */
trait MovementTripFixtures
{
    private ?User $fixtureActor = null;
    private ?VehicleType $fixtureType = null;

    protected function trips(): TripSchedulingService
    {
        return app(TripSchedulingService::class);
    }

    protected function actor(): User
    {
        return $this->fixtureActor ??= User::factory()->create(['status' => 'active']);
    }

    protected function local(string $value): CarbonImmutable
    {
        return MovementTime::toUtc($value);
    }

    /** An approved request with one outbound leg (inserted directly; the request workflow has its own tests). */
    protected function approvedLeg(int $pax, string $requiredArrivalLocal, string $origin = 'Amman', string $destination = 'Irbid', string $status = MovementRequest::STATUS_APPROVED): MovementRequestLeg
    {
        $request = MovementRequest::create([
            'requester_id' => User::factory()->create(['status' => 'active'])->id,
            'trip_direction' => MovementRequest::DIRECTION_ONE_WAY,
            'status' => $status,
            'fulfilment_status' => $status === MovementRequest::STATUS_APPROVED ? MovementRequest::FULFILMENT_UNALLOCATED : MovementRequest::FULFILMENT_NOT_APPLICABLE,
        ]);
        $request->update(['reference_no' => sprintf('MV-2030-%06d', $request->id)]);
        $arrival = $this->local($requiredArrivalLocal);

        return MovementRequestLeg::create([
            'movement_request_id' => $request->id,
            'leg_type' => MovementRequestLeg::TYPE_OUTBOUND,
            'origin_text' => $origin,
            'destination_text' => $destination,
            'passenger_count' => $pax,
            'travel_date' => MovementTime::toLocal($arrival)->format('Y-m-d'),
            'required_arrival_at' => $arrival,
        ]);
    }

    protected function vehicle(int $capacity = 30, array $attributes = []): Vehicle
    {
        $this->fixtureType ??= VehicleType::query()->firstOrCreate(['code' => 'bus'], ['name_ar' => 'حافلة', 'name_en' => 'Bus', 'size_class' => 'large']);

        return Vehicle::create($attributes + [
            'fleet_number' => 'FLT-'.uniqid(),
            'vehicle_type_id' => $this->fixtureType->id,
            'passenger_capacity' => $capacity,
            'operational_status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    protected function driver(array $attributes = []): Driver
    {
        return Driver::create($attributes + ['name' => 'Driver '.uniqid(), 'employment_status' => Driver::STATUS_ACTIVE]);
    }

    /** A draft trip with custom stops (all allow pickup and drop-off) and, optionally, seats offered. */
    protected function tripWithStops(array $names, ?int $seats = null): Trip
    {
        $trip = $this->trips()->createDraft($this->actor());
        foreach ($names as $name) {
            $this->trips()->addStop($trip, $this->actor(), ['name' => $name]);
        }
        if ($seats !== null) {
            $this->trips()->setSeatCapacity($trip, $this->actor(), $seats);
        }

        return $trip->fresh();
    }

    protected function stopAt(Trip $trip, int $sequence): TripStop
    {
        return $trip->stops()->where('sequence', $sequence)->firstOrFail();
    }

    /** Time a trip from a local departure, with the given free-flow minutes per section (dwell defaults to config). */
    protected function timed(Trip $trip, string $departLocal, array $sectionMinutes, array $dwellMinutes = []): Trip
    {
        $stops = $trip->stops()->get()->values();
        $running = [];
        foreach ($sectionMinutes as $i => $minutes) {
            $running[$stops[$i + 1]->id] = $minutes;
        }
        $dwells = [];
        foreach ($dwellMinutes as $i => $minutes) {
            $dwells[$stops[$i]->id] = $minutes;
        }

        return $this->trips()->setTimetable($trip, $this->actor(), $running, $dwells, ['type' => 'depart_at', 'at' => $this->local($departLocal)]);
    }

    /** A two-stop trip timed from $departLocal with one section of $minutes, a vehicle and a driver, scheduled. */
    protected function scheduledTrip(string $departLocal, int $minutes, Vehicle $vehicle, Driver $driver, string $status = Trip::STATUS_SCHEDULED): Trip
    {
        $trip = $this->timed($this->tripWithStops(['From', 'To']), $departLocal, [$minutes]);
        $trip = $this->trips()->assign($trip, $this->actor(), $vehicle->id, $driver->id);
        if ($status === Trip::STATUS_SCHEDULED) {
            $trip = $this->trips()->schedule($trip, $this->actor());
        }

        return $trip->fresh();
    }

    protected function localTime(?\DateTimeInterface $utc): ?string
    {
        return $utc ? MovementTime::toLocal($utc)->format('Y-m-d H:i') : null;
    }
}
