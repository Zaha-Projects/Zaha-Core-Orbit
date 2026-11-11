<?php

namespace Tests\Feature;

use Illuminate\Routing\RedirectController;
use Tests\TestCase;

/**
 * Movement Phase 1: the never-used legacy A fleet-trip subsystem (trips, trip_segments, trip_rounds)
 * is fully retired, while legacy B (transport requests) and C (movement days) stay untouched until
 * their own phases. DB-free on purpose (see docs/movement-module-implementation-plan.md §3).
 */
class MovementLegacyFleetTripRetirementTest extends TestCase
{
    /**
     * @dataProvider retiredRouteNames
     */
    public function test_legacy_fleet_trip_routes_are_removed(string $name): void
    {
        $this->assertNull(app('router')->getRoutes()->getByName($name), "Retired route [{$name}] is still registered.");
    }

    public function retiredRouteNames(): array
    {
        return array_map(fn (string $name) => [$name], [
            'role.transport.trips.index',
            'role.transport.trips.create',
            'role.transport.trips.store',
            'role.transport.trips.edit',
            'role.transport.trips.update',
            'role.transport.trips.close',
            'role.transport.segments.store',
            'role.transport.segments.update',
            'role.transport.segments.destroy',
            'role.transport.rounds.store',
            'role.transport.rounds.update',
            'role.transport.rounds.destroy',
        ]);
    }

    public function test_legacy_transport_report_name_now_redirects_to_the_reports_hub(): void
    {
        $route = app('router')->getRoutes()->getByName('role.reports.transport.index');

        $this->assertNotNull($route);
        $this->assertSame('dashboard/reports/transport', $route->uri());
        $this->assertSame(RedirectController::class, ltrim($route->getActionName(), '\\'));
        $this->assertSame('/dashboard/reports/overview', $route->defaults['destination'] ?? null);
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    /**
     * @dataProvider untouchedRouteNames
     */
    public function test_legacy_b_and_c_routes_remain_until_their_own_phases(string $name): void
    {
        // B route names now resolve as redirects (Phase 4B); see MovementLegacyTransportRequestRetirementTest.
        $this->assertLegacyRouteExists($name);
    }

    private function assertLegacyRouteExists(string $name): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName($name), "Route [{$name}] must not be retired in Phase 1.");
    }

    public function untouchedRouteNames(): array
    {
        return array_map(fn (string $name) => [$name], [
            'role.transport.vehicles.index',
            'role.transport.drivers.index',
            'role.transport.movements.index',
            'role.transport.movements.store',
            'role.transport_officer.dashboard',
        ]);
    }

    public function test_legacy_fleet_trip_classes_and_views_are_gone(): void
    {
        foreach ([
            'App\\Models\\Trip',
            'App\\Models\\TripSegment',
            'App\\Models\\TripRound',
            'App\\Http\\Controllers\\Web\\Transport\\TripsController',
            'App\\Http\\Controllers\\Roles\\Transport\\TripSegmentsController',
            'App\\Http\\Controllers\\Roles\\Transport\\TripRoundsController',
            'App\\Http\\Controllers\\Web\\Reports\\TransportReportsController',
        ] as $class) {
            $this->assertFalse(class_exists($class), "Retired class [{$class}] still exists.");
        }

        $this->assertFalse(view()->exists('pages.transport.trips.index'));
        $this->assertFalse(view()->exists('pages.reports.transport'));

        // Inspect source rather than loading the models, so this check does not depend on the
        // optional runtime packages (e.g. Spatie) that User pulls in.
        $this->assertStringNotContainsString('function tripsCreated(', file_get_contents(app_path('Models/User.php')));
        $this->assertStringNotContainsString('function trips(', file_get_contents(app_path('Models/Vehicle.php')));
        $this->assertStringNotContainsString('function trips(', file_get_contents(app_path('Models/Driver.php')));

        foreach (['app', 'routes', 'config', 'database/seeders'] as $directory) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($directory), \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $source = file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    '/App\\\\Models\\\\Trip(Segment|Round)?\b|role\.transport\.(trips|segments|rounds)\./',
                    $source,
                    'Legacy fleet-trip reference left in '.$file->getPathname()
                );
            }
        }
    }

    public function test_fake_movement_seed_data_is_not_in_the_default_seeding_path(): void
    {
        $this->assertFalse(class_exists('Database\\Seeders\\MovementSeeder'));
        $this->assertStringNotContainsString(
            'MovementSeeder',
            file_get_contents(database_path('seeders/DatabaseSeeder.php'))
        );
    }
}
