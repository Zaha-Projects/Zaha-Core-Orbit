<?php

namespace Tests\Unit;

use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\Vehicle;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/** DB-free checks for Phase 3 model helpers (Revision 4 items 3 and 8, D8). */
class MovementFleetModelsTest extends TestCase
{
    public function test_maps_links_yield_coordinates_through_the_existing_parser(): void
    {
        $this->assertSame([31.99981, 35.83551], Location::coordinatesFromMapsUrl('https://www.google.com/maps/place/X/@31.9,35.8,17z/data=!3d31.99981!4d35.83551'));
        $this->assertSame([31.95, 35.91], Location::coordinatesFromMapsUrl('https://www.google.com/maps/@31.95,35.91,15z'));
        $this->assertSame([32.5, 35.85], Location::coordinatesFromMapsUrl('https://maps.google.com/?q=32.5,35.85'));
        $this->assertNull(Location::coordinatesFromMapsUrl('https://maps.google.com/?q=Khalda+Amman'));
        $this->assertNull(Location::coordinatesFromMapsUrl('https://maps.google.com/?q=95,35'));
        $this->assertNull(Location::coordinatesFromMapsUrl(null));
    }

    public function test_licence_state_flags_expired_and_expiring_within_30_days(): void
    {
        $today = CarbonImmutable::parse('2026-11-11', 'Asia/Amman');
        $state = fn (?string $date) => (new Driver(['licence_expires_on' => $date]))->licenceState($today);

        $this->assertSame(Driver::LICENCE_UNKNOWN, $state(null));
        $this->assertSame(Driver::LICENCE_EXPIRED, $state('2026-11-10'));
        $this->assertSame(Driver::LICENCE_EXPIRING, $state('2026-11-11'));
        $this->assertSame(Driver::LICENCE_EXPIRING, $state('2026-12-11'));
        $this->assertSame(Driver::LICENCE_OK, $state('2026-12-12'));
    }

    public function test_vehicle_is_classified_only_with_type_and_capacity(): void
    {
        $this->assertFalse((new Vehicle())->isClassified());
        $this->assertFalse((new Vehicle(['vehicle_type_id' => 1]))->isClassified());
        $this->assertFalse((new Vehicle(['vehicle_type_id' => 1, 'passenger_capacity' => 0]))->isClassified());
        $this->assertTrue((new Vehicle(['vehicle_type_id' => 1, 'passenger_capacity' => 12]))->isClassified());
    }
}
