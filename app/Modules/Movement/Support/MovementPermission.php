<?php

namespace App\Modules\Movement\Support;

/**
 * Movement permission names (docs/movement-module-implementation-plan.md §14.1).
 * Seeded by Database\Seeders\MovementPermissionSeeder; granted per §14.2.
 */
final class MovementPermission
{
    public const REQUESTS_CREATE = 'movement.requests.create';
    public const REQUESTS_VIEW_ALL = 'movement.requests.view_all';
    public const REQUESTS_REVIEW = 'movement.requests.review';
    public const TRIPS_PLAN = 'movement.trips.plan';
    public const TRIPS_PUBLISH = 'movement.trips.publish';
    public const TRIPS_MERGE = 'movement.trips.merge';
    public const TRIPS_CANCEL = 'movement.trips.cancel';
    public const TRIPS_EXECUTE = 'movement.trips.execute';
    public const BOOKINGS_MANAGE = 'movement.bookings.manage';
    public const VEHICLES_MANAGE = 'movement.vehicles.manage';
    public const DRIVERS_MANAGE = 'movement.drivers.manage';
    public const DRIVERS_VIEW_DETAILS = 'movement.drivers.view_details';
    public const LOCATIONS_MANAGE = 'movement.locations.manage';
    public const REPORTS_VIEW = 'movement.reports.view';
    public const DRIVER_WORKSPACE = 'movement.driver.workspace';

    /**
     * @return array<int, string> every movement permission, in §14.1 order
     */
    public static function all(): array
    {
        return [
            self::REQUESTS_CREATE,
            self::REQUESTS_VIEW_ALL,
            self::REQUESTS_REVIEW,
            self::TRIPS_PLAN,
            self::TRIPS_PUBLISH,
            self::TRIPS_MERGE,
            self::TRIPS_CANCEL,
            self::TRIPS_EXECUTE,
            self::BOOKINGS_MANAGE,
            self::VEHICLES_MANAGE,
            self::DRIVERS_MANAGE,
            self::DRIVERS_VIEW_DETAILS,
            self::LOCATIONS_MANAGE,
            self::REPORTS_VIEW,
            self::DRIVER_WORKSPACE,
        ];
    }

    private function __construct()
    {
    }
}
