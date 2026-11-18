<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Movement Phase 5A: legacy C (driver daily movements: movement_days and the old movement_trips) is retired
 * (plan §18.3, Revision 6). The old vehicles/drivers screens and the transport-officer dashboard stay until 5B.
 * DB-free on purpose.
 */
class MovementLegacyMovementDayRetirementTest extends TestCase
{
    /** @dataProvider retiredRouteNames */
    public function test_c_routes_are_removed(string $name): void
    {
        $this->assertNull(app('router')->getRoutes()->getByName($name), "Retired route [{$name}] is still registered.");
    }

    public function retiredRouteNames(): array
    {
        return array_map(fn (string $name) => [$name], [
            'role.transport.movements.index',
            'role.transport.movements.create',
            'role.transport.movements.store',
            'role.transport.movements.show',
            'role.transport.movements.edit',
            'role.transport.movements.update',
            'role.transport.movements.destroy',
        ]);
    }

    public function test_c_classes_views_assets_and_lang_are_gone(): void
    {
        foreach (['App\\Http\\Controllers\\Web\\Transport\\MovementsController', 'App\\Models\\MovementDay', 'App\\Models\\MovementTrip'] as $class) {
            $this->assertFalse(class_exists($class), "Retired class [{$class}] still exists.");
        }
        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $this->assertFalse(view()->exists("pages.transport.movements.{$view}"));
        }
        $this->assertFileDoesNotExist(public_path('assets/js/pages/pages-transport-movements-form.js'));
        $this->assertFileDoesNotExist(public_path('assets/js/pages/pages-transport-movements-form.min.js'));

        foreach (['ar', 'en'] as $locale) {
            $lang = require resource_path("lang/{$locale}/app.php");
            $this->assertArrayNotHasKey('movements', $lang['roles']['transport'], "{$locale}: roles.transport.movements must be removed.");
            $this->assertArrayHasKey('vehicles', $lang['roles']['transport'], 'The old-master lang stays until 5B.');
            $this->assertArrayHasKey('drivers', $lang['roles']['transport']);
        }

        $this->assertStringNotContainsString('function movementDays(', file_get_contents(app_path('Models/Driver.php')));
        $this->assertStringNotContainsString('function movementTrips(', file_get_contents(app_path('Models/Vehicle.php')));
    }

    public function test_navigation_no_longer_links_to_c(): void
    {
        $this->assertStringNotContainsString('role.transport.movements', file_get_contents(resource_path('views/layouts/app.blade.php')));

        $dashboard = file_get_contents(resource_path('views/roles/transport_officer/dashboard.blade.php'));
        $this->assertStringNotContainsString('role.transport.movements', $dashboard);
        $this->assertStringContainsString('role.transport.vehicles.index', $dashboard, 'The officer dashboard stays until 5B.');
    }

    public function test_no_code_references_c_any_more(): void
    {
        $hits = [];
        foreach (['app', 'routes', 'config', 'resources/views', 'database/seeders'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.php')
                    && preg_match('/\bMovementDay\b|movement_days|App\\\\Models\\\\MovementTrip\b|MovementsController|transport\.movements|pages-transport-movements/', file_get_contents($file->getPathname()))) {
                    $hits[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $hits);
    }

    /** @dataProvider keptRouteNames */
    public function test_old_master_routes_remain_until_5b(string $name): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName($name), "Route [{$name}] must remain until 5B.");
    }

    public function keptRouteNames(): array
    {
        return array_map(fn (string $name) => [$name], [
            'role.transport.vehicles.index',
            'role.transport.drivers.index',
            'role.transport_officer.dashboard',
        ]);
    }
}
