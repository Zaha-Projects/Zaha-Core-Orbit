<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Support\MovementPermission as P;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Movement Phase 3 (plan §19 Phase 3, Revision 4): §14.3 rows for fleet, drivers and locations,
 * Form Request rules, linking, audit and the public overview privacy guarantee (D7).
 * Database test — run only against a disposable database (CLAUDE.md, database safety).
 */
class MovementFleetHttpTest extends TestCase
{
    use RefreshDatabase;

    private const MANAGER_PAGES = [
        'movement.vehicles.index',
        'movement.vehicles.create',
        'movement.vehicle-types.index',
        'movement.drivers.index',
        'movement.drivers.create',
        'movement.locations.index',
        'movement.locations.create',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(rightDatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function bus(): VehicleType
    {
        return VehicleType::query()->where('code', 'bus')->firstOrFail();
    }

    private function vehicle(array $overrides = []): Vehicle
    {
        return Vehicle::create($overrides + [
            'fleet_number' => 'FLT-'.uniqid(),
            'vehicle_type_id' => $this->bus()->id,
            'passenger_capacity' => 30,
            'operational_status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    private function auditCount(string $action): int
    {
        return AuditLog::query()->where('module', 'movement')->where('action', $action)->count();
    }

    // ---- §14.3 matrix -------------------------------------------------------------------------

    public function test_manager_and_super_admin_can_open_every_manager_page(): void
    {
        foreach (['movement_manager', 'super_admin'] as $role) {
            $user = $this->user($role);
            foreach (self::MANAGER_PAGES as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
        }
    }

    public function test_requesters_and_ordinary_users_are_denied_on_manager_pages_and_writes(): void
    {
        $vehicle = $this->vehicle();
        $driver = Driver::create(['name' => 'D', 'employment_status' => Driver::STATUS_ACTIVE]);

        foreach (['relations_officer', 'supervisor', 'staff'] as $role) {
            $user = $this->user($role);
            foreach (self::MANAGER_PAGES as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
            $this->actingAs($user)->get(route('movement.vehicles.show', $vehicle))->assertForbidden();
            $this->actingAs($user)->get(route('movement.drivers.show', $driver))->assertForbidden();
            // Writes are refused before validation (403, not 422/302).
            $this->actingAs($user)->post(route('movement.vehicles.store'), [])->assertForbidden();
            $this->actingAs($user)->post(route('movement.drivers.store'), [])->assertForbidden();
            $this->actingAs($user)->post(route('movement.locations.store'), [])->assertForbidden();
            $this->actingAs($user)->post(route('movement.vehicle-types.store'), [])->assertForbidden();
            $this->actingAs($user)->post(route('movement.vehicles.status', $vehicle), [])->assertForbidden();
            $this->actingAs($user)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id])->assertForbidden();
            $this->actingAs($user)->delete(route('movement.vehicles.destroy', $vehicle))->assertForbidden();
        }
        $this->assertNull($vehicle->fresh()->deleted_at);
    }

    public function test_per_user_denial_wins_over_the_manager_role(): void
    {
        $manager = $this->user('movement_manager');
        $manager->deniedPermissions()->attach(Permission::findByName(P::VEHICLES_MANAGE, 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $manager = $manager->fresh();

        $this->actingAs($manager)->get(route('movement.vehicles.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('movement.vehicle-types.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('movement.drivers.index'))->assertOk();
        $this->actingAs($manager)->get(route('movement.locations.index'))->assertOk();
    }

    public function test_per_user_grant_opens_a_manager_page_for_an_ordinary_user(): void
    {
        $staff = $this->user('staff');
        $staff->givePermissionTo(P::LOCATIONS_MANAGE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($staff->fresh())->get(route('movement.locations.index'))->assertOk();
        $this->actingAs($staff->fresh())->get(route('movement.vehicles.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('movement.fleet.overview'))->assertRedirect(route('login'));
        $this->get(route('movement.vehicles.index'))->assertRedirect(route('login'));
    }

    // ---- Public overview privacy (D7) -------------------------------------------------------------

    public function test_public_overview_shows_aggregates_only(): void
    {
        $minibus = VehicleType::query()->where('code', 'minibus')->firstOrFail();
        $this->vehicle(['fleet_number' => 'FLT-SECRET-71', 'plate_number' => 'PLT-SECRET-81']);
        $this->vehicle(['fleet_number' => 'FLT-SECRET-72', 'plate_number' => 'PLT-SECRET-82']);
        $this->vehicle(['fleet_number' => 'FLT-SECRET-73', 'operational_status' => Vehicle::STATUS_MAINTENANCE, 'status_reason' => 'REASON-SECRET']);
        $this->vehicle(['fleet_number' => 'FLT-SECRET-74', 'vehicle_type_id' => $minibus->id, 'passenger_capacity' => 14]);
        $this->vehicle(['fleet_number' => 'FLT-SECRET-75', 'operational_status' => Vehicle::STATUS_INACTIVE]);
        Vehicle::create(['fleet_number' => 'FLT-SECRET-76', 'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION]);
        Driver::create(['name' => 'DRIVER-SECRET-NAME', 'phone' => '0799990001', 'licence_number' => 'LIC-SECRET-1', 'employment_status' => Driver::STATUS_ACTIVE]);
        Driver::create(['name' => 'DRIVER-SECRET-OTHER', 'phone' => '0799990002', 'employment_status' => Driver::STATUS_INACTIVE]);

        foreach (['staff', 'relations_officer', 'movement_manager'] as $role) {
            $response = $this->actingAs($this->user($role))->get(route('movement.fleet.overview'))->assertOk();
            $html = $response->getContent();

            foreach (['FLT-SECRET', 'PLT-SECRET', 'REASON-SECRET', 'DRIVER-SECRET', '07999900', 'LIC-SECRET'] as $secret) {
                $this->assertStringNotContainsString($secret, $html, "Overview leaked [{$secret}] to {$role}.");
            }

            // Snapshot of the aggregate figures: bus 3 (2 operational, 1 unavailable, 3×30), minibus 1×14; 1 active driver.
            $response->assertViewHas('activeDrivers', 1);
            $this->assertSame([
                ['size_class' => 'large', 'total' => 3, 'operational' => 2, 'unavailable' => 1, 'capacities' => [30 => 3]],
                ['size_class' => 'medium', 'total' => 1, 'operational' => 1, 'unavailable' => 0, 'capacities' => [14 => 1]],
            ], $response->viewData('types')->map(fn (array $row) => array_diff_key($row, ['name' => true]))->all());
        }
    }

    // ---- Form Requests -----------------------------------------------------------------------------

    public function test_vehicle_validation_rules(): void
    {
        $manager = $this->user('movement_manager');
        $deleted = $this->vehicle(['fleet_number' => 'FLT-OLD', 'plate_number' => 'PLT-OLD']);
        $deleted->delete();

        $base = ['fleet_number' => 'FLT-NEW', 'vehicle_type_id' => $this->bus()->id, 'passenger_capacity' => 20, 'operational_status' => 'operational'];

        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['fleet_number' => 'FLT-OLD'] + $base)
            ->assertSessionHasErrors(['fleet_number' => __('movement.validation.unique_including_deleted')]);
        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['plate_number' => 'PLT-OLD'] + $base)
            ->assertSessionHasErrors('plate_number');
        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['passenger_capacity' => 0] + $base)
            ->assertSessionHasErrors('passenger_capacity');
        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['operational_status' => 'flying'] + $base)
            ->assertSessionHasErrors('operational_status');
        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['operational_status' => 'pending_verification'] + $base)
            ->assertSessionHasErrors('operational_status');
        $this->actingAs($manager)->post(route('movement.vehicles.store'), ['vehicle_type_id' => null] + $base)
            ->assertSessionHasErrors('vehicle_type_id');

        $this->actingAs($manager)->post(route('movement.vehicles.store'), $base)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('movement_vehicles', ['fleet_number' => 'FLT-NEW', 'passenger_capacity' => 20]);
    }

    public function test_vehicle_status_change_requires_reason_and_a_classified_vehicle(): void
    {
        $manager = $this->user('movement_manager');
        $vehicle = $this->vehicle();

        $this->actingAs($manager)->post(route('movement.vehicles.status', $vehicle), ['operational_status' => 'maintenance'])
            ->assertSessionHasErrors('status_reason');
        $this->actingAs($manager)->post(route('movement.vehicles.status', $vehicle), ['operational_status' => 'nope', 'status_reason' => 'x'])
            ->assertSessionHasErrors('operational_status');

        $imported = Vehicle::create(['fleet_number' => 'IMP-1', 'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION]);
        $this->actingAs($manager)->post(route('movement.vehicles.status', $imported), ['operational_status' => 'operational', 'status_reason' => 'checked'])
            ->assertSessionHasErrors(['operational_status' => __('movement.validation.classify_first')]);
        $this->assertSame(Vehicle::STATUS_PENDING_VERIFICATION, $imported->fresh()->operational_status);

        $this->actingAs($manager)->put(route('movement.vehicles.update', $imported), ['fleet_number' => 'IMP-1', 'vehicle_type_id' => $this->bus()->id, 'passenger_capacity' => 25])->assertSessionHasNoErrors();
        $this->actingAs($manager)->post(route('movement.vehicles.status', $imported), ['operational_status' => 'operational', 'status_reason' => 'checked'])->assertSessionHasNoErrors();
        $fresh = $imported->fresh();
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $fresh->operational_status);
        $this->assertSame('checked', $fresh->status_reason);
        $this->assertSame($manager->id, $fresh->status_changed_by);
    }

    public function test_driver_and_type_uniqueness_include_soft_deleted_rows(): void
    {
        $manager = $this->user('movement_manager');
        Driver::create(['name' => 'Old', 'licence_number' => 'LIC-1', 'employment_status' => 'active'])->delete();

        $this->actingAs($manager)->post(route('movement.drivers.store'), ['name' => 'New', 'licence_number' => 'LIC-1', 'employment_status' => 'active'])
            ->assertSessionHasErrors(['licence_number' => __('movement.validation.unique_including_deleted')]);
        $this->actingAs($manager)->post(route('movement.drivers.store'), ['name' => 'New', 'employment_status' => 'pending_verification'])
            ->assertSessionHasErrors('employment_status');
        $this->actingAs($manager)->post(route('movement.drivers.store'), ['name' => 'New', 'employment_status' => 'active', 'licence_expires_on' => '31/12/2026'])
            ->assertSessionHasErrors('licence_expires_on');

        $this->bus()->delete();
        $this->actingAs($manager)->post(route('movement.vehicle-types.store'), ['code' => 'bus', 'name_ar' => 'ب', 'name_en' => 'B', 'size_class' => 'large'])
            ->assertSessionHasErrors(['code' => __('movement.validation.unique_including_deleted')]);
        $this->actingAs($manager)->post(route('movement.vehicle-types.store'), ['code' => 'coach', 'name_ar' => 'ب', 'name_en' => 'B', 'size_class' => 'huge'])
            ->assertSessionHasErrors('size_class');
        $this->actingAs($manager)->post(route('movement.vehicle-types.store'), ['code' => 'coach', 'name_ar' => 'ب', 'name_en' => 'B', 'size_class' => 'large', 'default_capacity' => 0])
            ->assertSessionHasErrors('default_capacity');
    }

    public function test_unavailability_rules_and_utc_storage(): void
    {
        $manager = $this->user('movement_manager');
        $vehicle = $this->vehicle();
        $driver = Driver::create(['name' => 'D', 'employment_status' => 'active']);
        $window = ['type' => 'maintenance', 'starts_at' => '2026-12-01 10:00', 'ends_at' => '2026-12-01 12:00'];

        $this->actingAs($manager)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id, 'ends_at' => '2026-12-01 10:00'] + $window)
            ->assertSessionHasErrors('ends_at');
        $this->actingAs($manager)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id] + $window)
            ->assertSessionHasErrors(['vehicle_id' => __('movement.validation.exactly_one_subject')]);
        $this->actingAs($manager)->post(route('movement.unavailabilities.store'), $window)
            ->assertSessionHasErrors('vehicle_id');
        $this->actingAs($manager)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id, 'type' => 'party'] + $window)
            ->assertSessionHasErrors('type');

        $this->actingAs($manager)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id] + $window)->assertSessionHasNoErrors();
        $stored = Unavailability::query()->firstOrFail();
        // Asia/Amman is UTC+3: 10:00 local is 07:00 UTC.
        $this->assertSame('2026-12-01 07:00:00', $stored->getRawOriginal('starts_at'));
        $this->assertSame('2026-12-01 09:00:00', $stored->getRawOriginal('ends_at'));

        // The model guard also refuses both or neither subject.
        $this->expectException(InvalidArgumentException::class);
        Unavailability::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'type' => 'other', 'starts_at' => now(), 'ends_at' => now()->addHour()]);
    }

    public function test_driver_leave_needs_drivers_manage_and_vehicle_windows_need_vehicles_manage(): void
    {
        $vehicle = $this->vehicle();
        $driver = Driver::create(['name' => 'D', 'employment_status' => 'active']);
        $window = ['type' => 'leave', 'starts_at' => '2026-12-01 10:00', 'ends_at' => '2026-12-02 10:00'];

        $staff = $this->user('staff');
        $staff->givePermissionTo(P::DRIVERS_MANAGE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $staff = $staff->fresh();

        $this->actingAs($staff)->post(route('movement.unavailabilities.store'), ['driver_id' => $driver->id] + $window)->assertSessionHasNoErrors();
        $this->actingAs($staff)->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id] + $window)->assertForbidden();
    }

    public function test_location_maps_link_fills_coordinates_and_validates_ranges(): void
    {
        $manager = $this->user('movement_manager');

        $this->actingAs($manager)->post(route('movement.locations.store'), [
            'name_ar' => 'مركز خلدا',
            'maps_url' => 'https://www.google.com/maps/place/Khalda/@31.9998,35.8355,17z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d31.99981!4d35.83551',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $location = Location::query()->firstOrFail();
        $this->assertEqualsWithDelta(31.99981, $location->latitude, 0.000001);
        $this->assertEqualsWithDelta(35.83551, $location->longitude, 0.000001);

        $this->actingAs($manager)->post(route('movement.locations.store'), ['address' => 'x'])->assertSessionHasErrors(['name_ar', 'name_en']);
        $this->actingAs($manager)->post(route('movement.locations.store'), ['name_en' => 'X', 'latitude' => 91, 'longitude' => 10])->assertSessionHasErrors('latitude');
        $this->actingAs($manager)->post(route('movement.locations.store'), ['name_en' => 'X', 'latitude' => 10])->assertSessionHasErrors('longitude');
    }

    // ---- Linking (§13.3) --------------------------------------------------------------------------

    public function test_linking_accepts_only_active_unlinked_users_and_unlinking_keeps_history(): void
    {
        $manager = $this->user('movement_manager');
        $driver = Driver::create(['name' => 'Driver A', 'employment_status' => 'active']);
        $other = Driver::create(['name' => 'Driver B', 'employment_status' => 'active']);
        $trashed = Driver::create(['name' => 'Driver C', 'employment_status' => 'active']);

        $inactive = User::factory()->create(['status' => 'inactive']);
        $this->actingAs($manager)->post(route('movement.drivers.link', $driver), ['user_id' => $inactive->id])
            ->assertSessionHasErrors(['user_id' => __('movement.validation.user_not_active')]);

        $taken = User::factory()->create(['status' => 'active']);
        $other->update(['user_id' => $taken->id]);
        $this->actingAs($manager)->post(route('movement.drivers.link', $driver), ['user_id' => $taken->id])
            ->assertSessionHasErrors(['user_id' => __('movement.validation.user_already_linked')]);

        $takenByTrashed = User::factory()->create(['status' => 'active']);
        $trashed->update(['user_id' => $takenByTrashed->id]);
        $trashed->delete();
        $this->actingAs($manager)->post(route('movement.drivers.link', $driver), ['user_id' => $takenByTrashed->id])
            ->assertSessionHasErrors('user_id');

        // The picker lists only active, unlinked users.
        $free = User::factory()->create(['status' => 'active', 'name' => 'Free Candidate']);
        $page = $this->actingAs($manager)->get(route('movement.drivers.show', ['driver' => $driver, 'user_search' => 'a']))->assertOk();
        $ids = $page->viewData('candidates')->pluck('id')->all();
        $this->assertContains($free->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertNotContains($taken->id, $ids);
        $this->assertNotContains($takenByTrashed->id, $ids);

        $this->actingAs($manager)->post(route('movement.drivers.link', $driver), ['user_id' => $free->id])->assertSessionHasNoErrors();
        $this->assertSame($free->id, $driver->fresh()->user_id);

        // A linked driver must be unlinked before linking another user.
        $another = User::factory()->create(['status' => 'active']);
        $this->actingAs($manager)->post(route('movement.drivers.link', $driver), ['user_id' => $another->id])
            ->assertSessionHasErrors(['user_id' => __('movement.validation.driver_already_linked')]);

        $usersBefore = User::query()->count();
        $this->actingAs($manager)->delete(route('movement.drivers.unlink', $driver))->assertRedirect();
        $this->assertNull($driver->fresh()->user_id);
        $this->assertNotNull(Driver::query()->find($driver->id), 'Unlinking must keep the driver row.');
        $this->assertSame($usersBefore, User::query()->count(), 'No account is created or deleted.');
        $this->assertSame(1, $this->auditCount('driver.user_linked'));
        $this->assertSame(1, $this->auditCount('driver.user_unlinked'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'driver.user_linked', 'entity_id' => $driver->id]);
    }

    // ---- Audit -------------------------------------------------------------------------------------

    public function test_every_write_is_audited(): void
    {
        $manager = $this->user('movement_manager');
        $this->actingAs($manager);

        $this->post(route('movement.vehicle-types.store'), ['code' => 'coach', 'name_ar' => 'كوتش', 'name_en' => 'Coach', 'size_class' => 'large', 'is_active' => 1])->assertSessionHasNoErrors();
        $type = VehicleType::query()->where('code', 'coach')->firstOrFail();
        $this->put(route('movement.vehicle-types.update', $type), ['code' => 'coach', 'name_ar' => 'كوتش', 'name_en' => 'Coach bus', 'size_class' => 'large', 'is_active' => 1])->assertSessionHasNoErrors();

        $this->post(route('movement.vehicles.store'), ['fleet_number' => 'A-1', 'vehicle_type_id' => $type->id, 'passenger_capacity' => 40, 'operational_status' => 'operational'])->assertSessionHasNoErrors();
        $vehicle = Vehicle::query()->where('fleet_number', 'A-1')->firstOrFail();
        $this->put(route('movement.vehicles.update', $vehicle), ['fleet_number' => 'A-1', 'vehicle_type_id' => $type->id, 'passenger_capacity' => 45])->assertSessionHasNoErrors();
        $this->post(route('movement.vehicles.status', $vehicle), ['operational_status' => 'maintenance', 'status_reason' => 'brakes'])->assertSessionHasNoErrors();
        $this->delete(route('movement.vehicles.destroy', $vehicle))->assertRedirect();
        $this->post(route('movement.vehicles.restore', $vehicle))->assertRedirect();

        $this->post(route('movement.drivers.store'), ['name' => 'Driver', 'employment_status' => 'active'])->assertSessionHasNoErrors();
        $driver = Driver::query()->where('name', 'Driver')->firstOrFail();
        $this->put(route('movement.drivers.update', $driver), ['name' => 'Driver X', 'employment_status' => 'inactive'])->assertSessionHasNoErrors();
        $this->delete(route('movement.drivers.destroy', $driver))->assertRedirect();
        $this->post(route('movement.drivers.restore', $driver))->assertRedirect();

        $this->post(route('movement.locations.store'), ['name_en' => 'Depot', 'is_active' => 1])->assertSessionHasNoErrors();
        $location = Location::query()->firstOrFail();
        $this->put(route('movement.locations.update', $location), ['name_en' => 'Main depot', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->delete(route('movement.locations.destroy', $location))->assertRedirect();
        $this->post(route('movement.locations.restore', $location))->assertRedirect();

        $this->post(route('movement.unavailabilities.store'), ['vehicle_id' => $vehicle->id, 'type' => 'maintenance', 'starts_at' => '2026-12-01 10:00', 'ends_at' => '2026-12-01 11:00'])->assertSessionHasNoErrors();
        $this->delete(route('movement.unavailabilities.destroy', Unavailability::query()->firstOrFail()))->assertRedirect();

        $this->delete(route('movement.vehicle-types.destroy', VehicleType::query()->where('code', 'van')->firstOrFail()))->assertRedirect();
        $this->post(route('movement.vehicle-types.restore', VehicleType::withTrashed()->where('code', 'van')->firstOrFail()))->assertRedirect();

        foreach ([
            'vehicle_type.created', 'vehicle_type.updated', 'vehicle_type.deleted', 'vehicle_type.restored',
            'vehicle.created', 'vehicle.updated', 'vehicle.status_changed', 'vehicle.deleted', 'vehicle.restored',
            'driver.created', 'driver.updated', 'driver.status_changed', 'driver.deleted', 'driver.restored',
            'location.created', 'location.updated', 'location.deleted', 'location.restored',
            'unavailability.created', 'unavailability.deleted',
        ] as $action) {
            $this->assertSame(1, $this->auditCount($action), "Expected one [{$action}] audit row.");
        }

        $status = AuditLog::query()->where('action', 'vehicle.status_changed')->firstOrFail();
        $this->assertEquals(['operational_status' => 'operational', 'status_reason' => null], $status->old_values);
        $this->assertEquals(['operational_status' => 'maintenance', 'status_reason' => 'brakes'], $status->new_values);
        $this->assertSame($manager->id, $status->user_id);
        $this->assertSame(Vehicle::class, $status->entity_type);

        $update = AuditLog::query()->where('action', 'vehicle.updated')->firstOrFail();
        $this->assertSame(['passenger_capacity' => 40], $update->old_values);
        $this->assertSame(['passenger_capacity' => 45], $update->new_values);
    }

    public function test_soft_deleted_records_are_restored_not_recreated(): void
    {
        $manager = $this->user('movement_manager');
        $vehicle = $this->vehicle(['fleet_number' => 'KEEP-1']);
        $this->actingAs($manager)->delete(route('movement.vehicles.destroy', $vehicle))->assertRedirect();
        $this->assertSoftDeleted('movement_vehicles', ['id' => $vehicle->id]);

        $this->actingAs($manager)->get(route('movement.vehicles.index', ['trashed' => 1]))->assertOk()->assertSee('KEEP-1');
        $this->actingAs($manager)->post(route('movement.vehicles.restore', $vehicle))->assertRedirect(route('movement.vehicles.show', $vehicle));
        $this->assertNull($vehicle->fresh()->deleted_at);
        $this->assertSame(1, Vehicle::withTrashed()->where('fleet_number', 'KEEP-1')->count());
    }

    // ---- Manager views -----------------------------------------------------------------------------

    public function test_manager_views_list_records_needing_verification_and_licence_flags(): void
    {
        $manager = $this->user('movement_manager');
        Vehicle::create(['fleet_number' => 'IMP-77', 'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION]);
        Driver::create(['name' => 'Imported Driver', 'employment_status' => Driver::STATUS_PENDING_VERIFICATION]);
        Driver::create(['name' => 'Expired Licence', 'employment_status' => 'active', 'licence_expires_on' => now('Asia/Amman')->subDay()->toDateString()]);
        Driver::create(['name' => 'Expiring Licence', 'employment_status' => 'active', 'licence_expires_on' => now('Asia/Amman')->addDays(10)->toDateString()]);

        $vehicles = $this->actingAs($manager)->get(route('movement.vehicles.index'))->assertOk();
        $this->assertSame(['IMP-77'], $vehicles->viewData('needsVerification')->pluck('fleet_number')->all());
        $vehicles->assertSee(__('movement.common.needs_verification'));

        $drivers = $this->actingAs($manager)->get(route('movement.drivers.index'))->assertOk();
        $this->assertSame(['Imported Driver'], $drivers->viewData('needsVerification')->pluck('name')->all());
        $drivers->assertSee('data-licence-state="expired"', false)->assertSee('data-licence-state="expiring"', false);
    }

    public function test_sidebar_shows_only_permitted_movement_items(): void
    {
        $staffHtml = $this->actingAs($this->user('staff'))->get(route('movement.fleet.overview'))->assertOk()->getContent();
        $this->assertStringContainsString(route('movement.fleet.overview'), $staffHtml);
        $this->assertStringNotContainsString(route('movement.vehicles.index'), $staffHtml);
        $this->assertStringNotContainsString(route('movement.drivers.index'), $staffHtml);
        $this->assertStringNotContainsString(route('movement.locations.index'), $staffHtml);

        $managerHtml = $this->actingAs($this->user('movement_manager'))->get(route('movement.fleet.overview'))->assertOk()->getContent();
        foreach (['movement.vehicles.index', 'movement.vehicle-types.index', 'movement.drivers.index', 'movement.locations.index'] as $route) {
            $this->assertStringContainsString(route($route), $managerHtml);
        }
    }
}
