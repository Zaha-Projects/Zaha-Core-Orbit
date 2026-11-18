<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Support\MovementPermission as P;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * TripPolicy: the §14.3 planning, assignment, scheduling and cancellation rows (Revision 6).
 * Policy level only; the HTTP rows arrive with the planner routes in 5A-2.
 * Database test — run only against a disposable database.
 */
class MovementTripPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const ABILITIES = ['viewAny', 'view', 'create', 'plan', 'schedule', 'unschedule', 'cancel'];

    private Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(rightDatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->trip = Trip::create(['status' => Trip::STATUS_DRAFT, 'pre_trip_buffer_minutes' => 15, 'post_trip_buffer_minutes' => 15]);
    }

    private function user(?string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['status' => 'active']);
        if ($role) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function abilities(User $user): array
    {
        $result = [];
        foreach (self::ABILITIES as $ability) {
            $result[$ability] = in_array($ability, ['viewAny', 'create'], true)
                ? $user->can($ability, Trip::class)
                : $user->can($ability, $this->trip);
        }

        return $result;
    }

    public function test_manager_and_super_admin_may_do_everything(): void
    {
        $all = array_fill_keys(self::ABILITIES, true);
        $this->assertSame($all, $this->abilities($this->user('movement_manager')));
        $this->assertSame($all, $this->abilities($this->user('super_admin')));
    }

    public function test_requesters_drivers_and_ordinary_users_may_do_nothing(): void
    {
        $none = array_fill_keys(self::ABILITIES, false);
        foreach (['relations_officer', 'supervisor', 'movement_driver', null] as $role) {
            $this->assertSame($none, $this->abilities($this->user($role)), 'Role: '.($role ?? 'none'));
        }
    }

    public function test_each_ability_follows_its_own_permission(): void
    {
        $planner = $this->user(null);
        $planner->givePermissionTo(P::TRIPS_PLAN);
        $this->assertSame(['viewAny' => true, 'view' => true, 'create' => true, 'plan' => true, 'schedule' => false, 'unschedule' => false, 'cancel' => false], $this->abilities($planner->fresh()));

        $scheduler = $this->user(null);
        $scheduler->givePermissionTo(P::TRIPS_PUBLISH);
        $this->assertSame(['viewAny' => true, 'view' => true, 'create' => false, 'plan' => false, 'schedule' => true, 'unschedule' => true, 'cancel' => false], $this->abilities($scheduler->fresh()));

        $canceller = $this->user(null);
        $canceller->givePermissionTo(P::TRIPS_CANCEL);
        $this->assertSame(['viewAny' => true, 'view' => true, 'create' => false, 'plan' => false, 'schedule' => false, 'unschedule' => false, 'cancel' => true], $this->abilities($canceller->fresh()));
    }

    public function test_per_user_denial_wins_and_inactive_users_are_refused(): void
    {
        $denied = $this->user('movement_manager');
        $denied->deniedPermissions()->attach(\Spatie\Permission\Models\Permission::findByName(P::TRIPS_CANCEL)->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $denied = $denied->fresh();
        $this->assertFalse($denied->can('cancel', $this->trip));
        $this->assertTrue($denied->can('plan', $this->trip));

        $this->assertSame(array_fill_keys(self::ABILITIES, false), $this->abilities($this->user('movement_manager', ['status' => 'inactive'])));
    }
}
