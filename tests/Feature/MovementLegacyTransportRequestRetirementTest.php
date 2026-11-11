<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Movement Phase 4B (plan §18.3, Revision 5): legacy B (transport requests) is retired.
 * DB-free: checks code, views, lang and routes without touching any database. The HTTP redirects
 * are in MovementLegacyTransportRequestRedirectTest (web requests write audit rows).
 */
class MovementLegacyTransportRequestRetirementTest extends TestCase
{
    public function test_b_classes_views_and_lang_are_gone(): void
    {
        foreach ([
            'App\\Models\\TransportRequest',
            'App\\Models\\TransportRequestTrip',
            'App\\Models\\TransportRequestAction',
            'App\\Models\\TransportRequestFeedback',
            'App\\Http\\Controllers\\Roles\\Transport\\TransportRequestsController',
        ] as $class) {
            $this->assertFileDoesNotExist(base_path(str_replace(['App\\', '\\'], ['app/', '/'], $class).'.php'), "{$class} must be removed.");
        }

        $this->assertFileDoesNotExist(resource_path('views/roles/transport/requests/index.blade.php'));

        foreach (['ar', 'en'] as $locale) {
            $lang = require resource_path("lang/{$locale}/app.php");
            $this->assertArrayNotHasKey('requests', $lang['roles']['transport'], "{$locale}: roles.transport.requests must be removed.");
            $this->assertArrayNotHasKey('transport_requests', $lang['roles']['transport_officer']['actions']);
            // C and the old masters keep their lang until Phase 5.
            $this->assertArrayHasKey('movements', $lang['roles']['transport']);
            $this->assertArrayHasKey('fleet', $lang['roles']['transport_officer']['actions']);
        }

        $dashboard = file_get_contents(resource_path('views/roles/transport_officer/dashboard.blade.php'));
        $this->assertStringNotContainsString('transport_requests', $dashboard);
        $this->assertStringNotContainsString('role.transport.requests', $dashboard);
        $this->assertStringContainsString('role.transport.movements.index', $dashboard, 'The officer dashboard stays, minus the B card.');
    }

    public function test_no_code_references_b_any_more(): void
    {
        $hits = [];
        foreach (['app', 'routes', 'resources/views', 'database/seeders'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.php')
                    && preg_match('/TransportRequest|transport_request/', file_get_contents($file->getPathname()))) {
                    $hits[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $hits);
    }

    public function test_c_and_old_master_routes_are_unchanged(): void
    {
        $expected = [
            'role.transport.movements.index' => 'MovementsController@index',
            'role.transport.movements.store' => 'MovementsController@store',
            'role.transport.movements.show' => 'MovementsController@show',
            'role.transport.movements.update' => 'MovementsController@update',
            'role.transport.movements.destroy' => 'MovementsController@destroy',
            'role.transport.vehicles.index' => 'VehiclesController@index',
            'role.transport.drivers.index' => 'DriversController@index',
            'role.transport_officer.dashboard' => 'DashboardController@index',
        ];

        foreach ($expected as $name => $action) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route [{$name}] must remain until Phase 5.");
            $this->assertStringEndsWith($action, $route->getActionName());
        }
    }
}
