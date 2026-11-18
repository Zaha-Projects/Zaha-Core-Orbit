<?php

namespace Tests\Unit;

use App\Modules\Movement\Services\SeatCapacityService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** SeatCapacityService (plan §11.1–11.3). Pure PHP: no database, no framework boot. */
class MovementSeatCapacityServiceTest extends TestCase
{
    private SeatCapacityService $seats;

    /** §11.3: 1 Amman → 2 Salt → 3 Jerash → 4 Irbid, capacity 30; A Amman→Irbid 15, B Amman→Salt 10, C Salt→Irbid 12. */
    private const EXAMPLE = [
        ['pickup' => 1, 'dropoff' => 4, 'pax' => 15],
        ['pickup' => 1, 'dropoff' => 2, 'pax' => 10],
        ['pickup' => 2, 'dropoff' => 4, 'pax' => 12],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seats = new SeatCapacityService();
    }

    public function test_worked_example_loads_and_matrix(): void
    {
        $this->assertSame([1 => 25, 2 => 27, 3 => 27], $this->seats->loads(4, self::EXAMPLE));
        $this->assertSame(27, $this->seats->maxLoad(4, self::EXAMPLE));
        $this->assertSame([
            1 => [2 => 5, 3 => 3, 4 => 3],
            2 => [3 => 3, 4 => 3],
            3 => [4 => 3],
        ], $this->seats->matrix(30, 4, self::EXAMPLE));
    }

    public function test_worked_example_consequences(): void
    {
        $spans = self::EXAMPLE;
        $this->assertTrue($this->seats->fits(30, 4, $spans, 1, 2, 5), 'Amman → Salt × 5 succeeds.');
        $spans[] = ['pickup' => 1, 'dropoff' => 2, 'pax' => 5];

        $this->assertFalse($this->seats->fits(30, 4, $spans, 1, 3, 1), 'Amman → Jerash × 1 fails: section 1 is full.');
        $this->assertTrue($this->seats->fits(30, 4, $spans, 3, 4, 3), 'Jerash → Irbid × 3 succeeds: it does not touch section 1.');
        $this->assertFalse($this->seats->fits(30, 4, $spans, 3, 4, 4));
    }

    public function test_every_pickup_dropoff_pair_matches_a_brute_force_count(): void
    {
        $stops = 6;
        $spans = [
            ['pickup' => 1, 'dropoff' => 3, 'pax' => 4],
            ['pickup' => 2, 'dropoff' => 6, 'pax' => 7],
            ['pickup' => 3, 'dropoff' => 4, 'pax' => 9],
            ['pickup' => 5, 'dropoff' => 6, 'pax' => 2],
            ['pickup' => 1, 'dropoff' => 6, 'pax' => 1],
        ];
        $loads = $this->seats->loads($stops, $spans);

        for ($p = 1; $p < $stops; $p++) {
            for ($d = $p + 1; $d <= $stops; $d++) {
                $max = 0;
                for ($k = $p; $k < $d; $k++) {
                    $onSection = 0;
                    foreach ($spans as $span) {
                        if ($span['pickup'] <= $k && $k < $span['dropoff']) { // occupies sections p … d−1
                            $onSection += $span['pax'];
                        }
                    }
                    $max = max($max, $onSection);
                }
                $this->assertSame(20 - $max, $this->seats->available(20, $loads, $p, $d), "Span {$p} → {$d}");
                $this->assertSame(20 - $max, $this->seats->matrix(20, $stops, $spans)[$p][$d]);
            }
        }
    }

    public function test_dropoff_stop_is_not_occupied(): void
    {
        // Passengers leave at their drop-off stop: a ride 2 → 3 does not touch section 3.
        $this->assertSame([1 => 0, 2 => 8, 3 => 0], $this->seats->loads(4, [['pickup' => 2, 'dropoff' => 3, 'pax' => 8]]));
    }

    public function test_empty_and_degenerate_routes(): void
    {
        $this->assertSame([], $this->seats->loads(1, []));
        $this->assertSame(0, $this->seats->maxLoad(3, []));
        $this->assertSame(12, $this->seats->available(12, $this->seats->loads(3, []), 1, 3));
    }

    /** @dataProvider invalidSpans */
    public function test_invalid_spans_are_rejected(int $pickup, int $dropoff): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->seats->loads(4, [['pickup' => $pickup, 'dropoff' => $dropoff, 'pax' => 1]]);
    }

    public function invalidSpans(): array
    {
        return ['same stop' => [2, 2], 'reversed' => [3, 1], 'before first' => [0, 2], 'after last' => [2, 5]];
    }

    public function test_zero_passengers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->seats->fits(10, 3, [], 1, 2, 0);
    }
}
