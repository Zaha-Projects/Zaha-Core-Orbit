<?php

namespace Tests\Unit;

use App\Modules\Movement\Support\MovementTime;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class MovementTimeTest extends TestCase
{
    public function test_movement_timezone_defaults_to_amman_without_changing_the_app_timezone(): void
    {
        $this->assertSame('Asia/Amman', MovementTime::timezone());
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_local_input_is_converted_to_utc(): void
    {
        $utc = MovementTime::toUtc('2026-11-10 07:25');

        $this->assertSame('UTC', $utc->getTimezone()->getName());
        $this->assertSame('2026-11-10 04:25:00', $utc->format('Y-m-d H:i:s'));
    }

    public function test_utc_values_are_displayed_in_local_time(): void
    {
        $this->assertSame('2026-11-10 07:25', MovementTime::toLocal('2026-11-10 04:25:00')->format('Y-m-d H:i'));
        $this->assertSame(
            '2026-11-10 07:25',
            MovementTime::toLocal(CarbonImmutable::parse('2026-11-10 04:25:00', 'UTC'))->format('Y-m-d H:i')
        );
    }

    public function test_a_trip_crossing_local_midnight_keeps_a_positive_duration(): void
    {
        $departure = MovementTime::toUtc('2026-11-10 23:30');
        $arrival = MovementTime::toUtc('2026-11-11 01:15');

        $this->assertTrue($arrival->greaterThan($departure));
        $this->assertSame(105, $departure->diffInMinutes($arrival));
        // 23:30 local on the 10th is still the 10th in UTC (20:30Z); 01:15 local on the 11th is 22:15Z on the 10th.
        $this->assertSame('2026-11-10 20:30', $departure->format('Y-m-d H:i'));
        $this->assertSame('2026-11-10 22:15', $arrival->format('Y-m-d H:i'));
    }

    public function test_local_day_bounds_are_expressed_in_utc(): void
    {
        [$start, $end] = MovementTime::localDayBoundsUtc('2026-11-10');

        $this->assertSame('2026-11-09 21:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-10 21:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $start->getTimezone()->getName());

        // A trailing time part resolves to the same local day.
        $this->assertEquals([$start, $end], MovementTime::localDayBoundsUtc('2026-11-10 18:00'));
        // DateTime input is bucketed by its LOCAL date: 20:30Z is 23:30 on the 10th in Amman...
        $this->assertEquals([$start, $end], MovementTime::localDayBoundsUtc(CarbonImmutable::parse('2026-11-10 20:30:00', 'UTC')));
        // ...while 22:30Z is already 01:30 on the 11th in Amman.
        [$nextStart] = MovementTime::localDayBoundsUtc(CarbonImmutable::parse('2026-11-10 22:30:00', 'UTC'));
        $this->assertSame('2026-11-10 21:00:00', $nextStart->format('Y-m-d H:i:s'));
    }

    public function test_configured_timezone_is_respected(): void
    {
        config(['movement.timezone' => 'UTC']);

        $this->assertSame('2026-11-10 07:25:00', MovementTime::toUtc('2026-11-10 07:25')->format('Y-m-d H:i:s'));
    }
}
