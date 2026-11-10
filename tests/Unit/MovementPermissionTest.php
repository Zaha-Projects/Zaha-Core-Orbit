<?php

namespace Tests\Unit;

use App\Modules\Movement\Support\MovementPermission;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MovementPermissionTest extends TestCase
{
    /** Plan §14.1, verbatim. */
    private const PLAN_14_1 = [
        'movement.requests.create',
        'movement.requests.view_all',
        'movement.requests.review',
        'movement.trips.plan',
        'movement.trips.publish',
        'movement.trips.merge',
        'movement.trips.cancel',
        'movement.trips.execute',
        'movement.bookings.manage',
        'movement.vehicles.manage',
        'movement.drivers.manage',
        'movement.drivers.view_details',
        'movement.locations.manage',
        'movement.reports.view',
        'movement.driver.workspace',
    ];

    public function test_constants_match_plan_section_14_1_exactly(): void
    {
        $this->assertSame(self::PLAN_14_1, MovementPermission::all());
    }

    public function test_there_are_no_duplicates_and_every_constant_is_listed(): void
    {
        $all = MovementPermission::all();
        $constants = array_values((new ReflectionClass(MovementPermission::class))->getConstants());

        $this->assertCount(15, $all);
        $this->assertSame($all, array_values(array_unique($all)));
        $this->assertEqualsCanonicalizing($constants, $all);

        foreach ($all as $name) {
            $this->assertMatchesRegularExpression('/^movement\.[a-z_]+\.[a-z_]+$/', $name);
        }
    }
}
