<?php

namespace Tests\Feature;

use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Services\MovementAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MovementTripFixtures;
use Tests\TestCase;

/**
 * summary() with Phase 5 inputs (Revision 5 deferral, Revision 6): trip windows and paired-trip reservations make
 * resources busy, draft trips do not, and the licence rule matches assignment (e − 1 s, local date).
 * Database test — run only against a disposable database.
 */
class MovementAvailabilitySummaryTripsTest extends TestCase
{
    use RefreshDatabase;
    use MovementTripFixtures;

    private function summary(string $fromLocal, string $untilLocal): array
    {
        return app(MovementAvailabilityService::class)->summary($this->local($fromLocal), $this->local($untilLocal));
    }

    public function test_scheduled_trips_and_paired_spans_count_as_busy_but_drafts_do_not(): void
    {
        $scheduledBus = $this->vehicle();
        $draftBus = $this->vehicle();
        $pairedBus = $this->vehicle();
        $this->vehicle(); // always free
        $this->scheduledTrip('2030-01-15 08:00', 60, $scheduledBus, $this->driver()); // blocked 07:45 – 09:24
        $this->scheduledTrip('2030-01-15 08:00', 60, $draftBus, $this->driver(), Trip::STATUS_DRAFT);

        $pairDriver = $this->driver();
        $outbound = $this->scheduledTrip('2030-01-15 06:00', 30, $pairedBus, $pairDriver);
        $return = $this->scheduledTrip('2030-01-15 16:00', 30, $pairedBus, $pairDriver, Trip::STATUS_DRAFT);
        $this->trips()->pair($outbound, $return, $this->actor(), true);

        $midday = $this->summary('2030-01-15 12:00', '2030-01-15 13:00');
        $this->assertSame(3, $midday['types'][0]['free'], 'Only the paired bus is reserved at midday.');
        $this->assertSame(['active' => 3, 'free' => 2], $midday['drivers'], 'The paired driver waits too (D22).');

        $morning = $this->summary('2030-01-15 08:30', '2030-01-15 09:00');
        $this->assertSame(['total' => 4, 'free' => 2, 'unavailable' => 2], array_intersect_key($morning['types'][0], array_flip(['total', 'free', 'unavailable'])));
    }

    public function test_summary_uses_the_assignment_licence_rule(): void
    {
        $this->driver(['licence_expires_on' => '2030-01-15']);

        $this->assertSame(1, $this->summary('2030-01-15 22:00', '2030-01-16 00:00')['drivers']['free'], 'Window ending at local midnight: valid.');
        $this->assertSame(0, $this->summary('2030-01-15 22:00', '2030-01-16 00:01')['drivers']['free']);
        $this->assertSame(1, $this->summary('2030-01-15 22:00', '2030-01-16 00:01')['drivers']['active'], 'Still active, only not free.');
    }
}
