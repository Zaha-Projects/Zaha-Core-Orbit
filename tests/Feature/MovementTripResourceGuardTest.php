<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\Vehicle;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\MovementTripFixtures;
use Tests\TestCase;

/**
 * §13.6 resource rules (Revision 4 item 7, Revision 6 item 5) through the Phase 3 fleet screens:
 * - blocked while on a draft or scheduled trip: vehicle status → maintenance / out_of_service / inactive,
 *   vehicle soft delete, driver deactivation, driver soft delete (the error names the trips);
 * - vehicle capacity cannot drop below the seats offered on such a trip.
 * Database test — run only against a disposable database.
 */
class MovementTripResourceGuardTest extends TestCase
{
    use RefreshDatabase;
    use MovementTripFixtures;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(rightDatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->manager = User::factory()->create(['status' => 'active']);
        $this->manager->assignRole('movement_manager');
    }

    private function message(Trip ...$trips): string
    {
        return __('movement.validation.resource_on_trips', ['trips' => collect($trips)->pluck('reference_no')->implode(', ')]);
    }

    public function test_vehicle_cannot_leave_service_or_be_deleted_while_on_a_draft_or_scheduled_trip(): void
    {
        $vehicle = $this->vehicle();
        $scheduled = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $this->driver());
        $draft = $this->scheduledTrip('2030-01-16 08:00', 60, $vehicle, $this->driver(), Trip::STATUS_DRAFT);

        foreach ([Vehicle::STATUS_MAINTENANCE, Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_INACTIVE] as $status) {
            $this->actingAs($this->manager)->post(route('movement.vehicles.status', $vehicle), ['operational_status' => $status, 'status_reason' => 'Workshop'])
                ->assertSessionHasErrors(['operational_status' => $this->message($scheduled, $draft)]);
        }
        $this->actingAs($this->manager)->delete(route('movement.vehicles.destroy', $vehicle))->assertSessionHasErrors(['vehicle' => $this->message($scheduled, $draft)]);
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $vehicle->fresh()->operational_status);
        $this->assertFalse($vehicle->fresh()->trashed());

        // Once the trips end without service, the vehicle may be withdrawn.
        $this->trips()->cancel($scheduled, $this->actor(), 'Rescheduled');
        $this->trips()->cancel($draft, $this->actor(), 'Rescheduled');
        $this->actingAs($this->manager)->post(route('movement.vehicles.status', $vehicle), ['operational_status' => Vehicle::STATUS_MAINTENANCE, 'status_reason' => 'Workshop'])
            ->assertSessionHasNoErrors();
        $this->assertSame(Vehicle::STATUS_MAINTENANCE, $vehicle->fresh()->operational_status);
        $this->actingAs($this->manager)->delete(route('movement.vehicles.destroy', $vehicle))->assertSessionHasNoErrors();
        $this->assertTrue(Vehicle::withTrashed()->find($vehicle->id)->trashed());
    }

    public function test_driver_cannot_be_deactivated_or_deleted_while_on_a_draft_or_scheduled_trip(): void
    {
        $driver = $this->driver(['name' => 'Samir']);
        $trip = $this->scheduledTrip('2030-01-15 08:00', 60, $this->vehicle(), $driver);

        $this->actingAs($this->manager)->put(route('movement.drivers.update', $driver), ['name' => 'Samir', 'employment_status' => 'inactive'])
            ->assertSessionHasErrors(['employment_status' => $this->message($trip)]);
        $this->actingAs($this->manager)->delete(route('movement.drivers.destroy', $driver))->assertSessionHasErrors(['driver' => $this->message($trip)]);
        $this->assertSame(Driver::STATUS_ACTIVE, $driver->fresh()->employment_status);

        // Other edits stay possible.
        $this->actingAs($this->manager)->put(route('movement.drivers.update', $driver), ['name' => 'Samir A.', 'employment_status' => 'active'])->assertSessionHasNoErrors();
        $this->assertSame('Samir A.', $driver->fresh()->name);

        $this->trips()->cancel($trip, $this->actor(), 'Rescheduled');
        $this->actingAs($this->manager)->put(route('movement.drivers.update', $driver), ['name' => 'Samir A.', 'employment_status' => 'inactive'])->assertSessionHasNoErrors();
        $this->assertSame(Driver::STATUS_INACTIVE, $driver->fresh()->employment_status);
    }

    public function test_vehicle_capacity_cannot_drop_below_the_seats_of_its_trips(): void
    {
        $vehicle = $this->vehicle(30, ['fleet_number' => 'BUS-1']);
        $trip = $this->scheduledTrip('2030-01-15 08:00', 60, $vehicle, $this->driver());
        $this->trips()->setSeatCapacity($trip, $this->actor(), 25);
        $form = fn (int $capacity) => ['fleet_number' => 'BUS-1', 'vehicle_type_id' => $vehicle->vehicle_type_id, 'passenger_capacity' => $capacity];

        $this->actingAs($this->manager)->put(route('movement.vehicles.update', $vehicle), $form(20))
            ->assertSessionHasErrors(['passenger_capacity' => __('movement.validation.capacity_below_trips', ['trips' => $trip->reference_no])]);
        $this->assertSame(30, $vehicle->fresh()->passenger_capacity);

        $this->actingAs($this->manager)->put(route('movement.vehicles.update', $vehicle), $form(25))->assertSessionHasNoErrors();
        $this->actingAs($this->manager)->put(route('movement.vehicles.update', $vehicle), $form(40))->assertSessionHasNoErrors();
        $this->assertSame(40, $vehicle->fresh()->passenger_capacity);
        $this->assertSame(25, $trip->fresh()->seat_capacity, 'Raising capacity never changes trips (§13.6).');
    }
}
