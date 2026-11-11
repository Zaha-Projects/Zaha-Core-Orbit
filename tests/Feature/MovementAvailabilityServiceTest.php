<?php

namespace Tests\Feature;

use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Models\VehicleType;
use App\Modules\Movement\Services\MovementAvailabilityService;
use Carbon\CarbonImmutable;
use Database\Seeders\MovementReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MovementAvailabilityService::summary() (plan §13.1–13.4, Phase 4 subset per Revision 5).
 * Database test — run only against a disposable database.
 */
class MovementAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $from;
    private CarbonImmutable $until;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MovementReferenceDataSeeder::class);
        $this->from = CarbonImmutable::parse('2026-12-01 07:00:00', 'UTC');
        $this->until = $this->from->addHours(3);
    }

    private function vehicle(string $type, int $capacity, string $status = Vehicle::STATUS_OPERATIONAL, string $fleet = null): Vehicle
    {
        return Vehicle::create([
            'fleet_number' => $fleet ?? 'SECRET-'.uniqid(),
            'plate_number' => 'PLATE-'.uniqid(),
            'vehicle_type_id' => VehicleType::query()->where('code', $type)->value('id'),
            'passenger_capacity' => $capacity,
            'operational_status' => $status,
        ]);
    }

    private function window(array $subject, string $start, string $end): void
    {
        Unavailability::create($subject + ['type' => 'maintenance', 'starts_at' => CarbonImmutable::parse($start, 'UTC'), 'ends_at' => CarbonImmutable::parse($end, 'UTC')]);
    }

    public function test_counts_respect_status_windows_and_capacity(): void
    {
        $busA = $this->vehicle('bus', 30);
        $busB = $this->vehicle('bus', 50);
        $this->vehicle('bus', 45, Vehicle::STATUS_MAINTENANCE);
        $this->vehicle('bus', 45, Vehicle::STATUS_INACTIVE); // not counted at all
        $this->vehicle('minibus', 14);
        Vehicle::create(['fleet_number' => 'IMPORTED', 'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION]); // no type: skipped

        $this->window(['vehicle_id' => $busA->id], '2026-12-01 08:00:00', '2026-12-01 09:00:00'); // overlaps
        $this->window(['vehicle_id' => $busB->id], '2026-12-01 10:00:00', '2026-12-01 12:00:00'); // starts exactly at window end: no overlap
        $this->window(['vehicle_id' => $busB->id], '2026-12-01 05:00:00', '2026-12-01 07:00:00'); // ends exactly at window start: no overlap

        $summary = app(MovementAvailabilityService::class)->summary($this->from, $this->until, 20);

        $bus = collect($summary['types'])->firstWhere('size_class', 'large');
        $this->assertSame(['total' => 3, 'free' => 1, 'unavailable' => 2, 'free_with_capacity' => 1, 'max_free_capacity' => 50],
            array_intersect_key($bus, array_flip(['total', 'free', 'unavailable', 'free_with_capacity', 'max_free_capacity'])));
        $minibus = collect($summary['types'])->firstWhere('total', 1);
        $this->assertSame(1, $minibus['free']);
        $this->assertSame(0, $minibus['free_with_capacity'], '14 seats do not fit 20 passengers.');
        $this->assertCount(2, $summary['types'], 'Types without vehicles are omitted.');
    }

    public function test_driver_counts_follow_section_13_2(): void
    {
        $free = Driver::create(['name' => 'Free', 'employment_status' => 'active']);
        $onLeave = Driver::create(['name' => 'Leave', 'employment_status' => 'active']);
        $adjacent = Driver::create(['name' => 'Adjacent', 'employment_status' => 'active']);
        Driver::create(['name' => 'Expired', 'employment_status' => 'active', 'licence_expires_on' => '2026-11-30']);
        Driver::create(['name' => 'Valid today', 'employment_status' => 'active', 'licence_expires_on' => '2026-12-01']);
        Driver::create(['name' => 'Inactive', 'employment_status' => 'inactive']);
        Driver::create(['name' => 'Imported', 'employment_status' => 'pending_verification']);

        $this->window(['driver_id' => $onLeave->id], '2026-11-30 00:00:00', '2026-12-02 00:00:00');
        $this->window(['driver_id' => $adjacent->id], '2026-12-01 10:00:00', '2026-12-01 18:00:00');

        $drivers = app(MovementAvailabilityService::class)->summary($this->from, $this->until)['drivers'];

        $this->assertSame(['active' => 5, 'free' => 3], $drivers);
        $this->assertNotNull($free);
    }

    public function test_output_contains_no_per_vehicle_or_driver_data(): void
    {
        $this->vehicle('bus', 30, Vehicle::STATUS_OPERATIONAL, 'FLEET-SECRET-1');
        Driver::create(['name' => 'DRIVER-SECRET', 'phone' => '0799000111', 'employment_status' => 'active']);

        $json = json_encode(app(MovementAvailabilityService::class)->summary($this->from, $this->until));

        foreach (['FLEET-SECRET', 'PLATE-', 'DRIVER-SECRET', '0799000111', '"id"', 'operational_status'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }
}
