<?php

namespace Tests\Feature;

use App\Modules\ActivityPlanning\MonthlyActivities\Http\Controllers\MonthlyActivitiesBrowseController;
use App\Modules\ActivityPlanning\MonthlyActivities\Http\Controllers\MonthlyActivityCalendarController;
use Illuminate\Routing\Route;
use Tests\TestCase;

class MonthlyActivityBrowseRouteContractTest extends TestCase
{
    /**
     * @dataProvider browseRouteProvider
     */
    public function test_browse_route_contract_is_stable(
        string $name,
        string $uri,
        string $action
    ): void {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($uri, $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame($action, $route->getActionName());
        $this->assertSame([], $route->parameterNames());
        $this->assertSame([
            'web',
            'auth',
            'role_or_permission:relations_manager|relations_officer|volunteer_coordinator|programs_manager|super_admin|monthly_activities.view',
            'branch.isolation',
        ], $route->gatherMiddleware());
    }

    public function browseRouteProvider(): array
    {
        return [
            'monthly activities index' => [
                'role.relations.activities.index',
                'dashboard/relations/monthly-activities',
                MonthlyActivitiesBrowseController::class . '@index',
            ],
            'monthly activities calendar' => [
                'role.relations.activities.calendar',
                'dashboard/relations/monthly-activities/calendar',
                MonthlyActivityCalendarController::class . '@index',
            ],
        ];
    }
}
