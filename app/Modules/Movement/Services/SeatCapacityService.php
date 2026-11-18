<?php

namespace App\Modules\Movement\Services;

use InvalidArgumentException;

/**
 * Per-section seat maths (plan §11.1–11.2). Pure PHP, no database access, no writes.
 *
 * Stops are numbered by sequence 1..n; section k runs from stop k to stop k+1 (1 ≤ k ≤ n−1).
 * A span with pickup p and drop-off d (p < d) occupies sections p … d−1, not section d.
 * Callers pass only counting bookings (confirmed / no_show, on trips that are not cancelled or merged, §11.1).
 *
 * A span is ['pickup' => int sequence, 'dropoff' => int sequence, 'pax' => int].
 */
class SeatCapacityService
{
    /**
     * Load per section, keyed 1..n−1 (§11.2, difference array, O(spans + stops)).
     *
     * @param iterable<array{pickup: int, dropoff: int, pax: int}> $spans
     * @return array<int, int>
     */
    public function loads(int $stopCount, iterable $spans): array
    {
        if ($stopCount < 2) {
            return [];
        }

        $diff = array_fill(1, $stopCount, 0);
        foreach ($spans as $span) {
            $this->assertSpan($stopCount, $span['pickup'], $span['dropoff']);
            $diff[$span['pickup']] += (int) $span['pax'];
            $diff[$span['dropoff']] -= (int) $span['pax'];
        }

        $loads = [];
        $running = 0;
        for ($k = 1; $k <= $stopCount - 1; $k++) {
            $running += $diff[$k];
            $loads[$k] = $running;
        }

        return $loads;
    }

    /** Highest load on any section; 0 for an empty trip. */
    public function maxLoad(int $stopCount, iterable $spans): int
    {
        $loads = $this->loads($stopCount, $spans);

        return $loads === [] ? 0 : max($loads);
    }

    /** Free seats for a ride from pickup p to drop-off d: seat_capacity − max load on sections p … d−1 (§11.1). */
    public function available(int $seatCapacity, array $loads, int $pickup, int $dropoff): int
    {
        $this->assertSpan(count($loads) + 1, $pickup, $dropoff);

        $max = 0;
        for ($k = $pickup; $k < $dropoff; $k++) {
            $max = max($max, $loads[$k] ?? 0);
        }

        return $seatCapacity - $max;
    }

    /** True when adding pax on [p, d) keeps every section within seat_capacity (§11.4). */
    public function fits(int $seatCapacity, int $stopCount, iterable $spans, int $pickup, int $dropoff, int $pax): bool
    {
        if ($pax < 1) {
            throw new InvalidArgumentException('A booking needs at least one passenger.');
        }

        return $this->available($seatCapacity, $this->loads($stopCount, $spans), $pickup, $dropoff) >= $pax;
    }

    /**
     * Free seats for every pickup → drop-off pair, [p][d] with p < d (§11.2; O(n²) with a running max).
     *
     * @return array<int, array<int, int>>
     */
    public function matrix(int $seatCapacity, int $stopCount, iterable $spans): array
    {
        $loads = $this->loads($stopCount, $spans);
        $matrix = [];
        for ($p = 1; $p < $stopCount; $p++) {
            $max = 0;
            for ($d = $p + 1; $d <= $stopCount; $d++) {
                $max = max($max, $loads[$d - 1]);
                $matrix[$p][$d] = $seatCapacity - $max;
            }
        }

        return $matrix;
    }

    private function assertSpan(int $stopCount, int $pickup, int $dropoff): void
    {
        if ($pickup < 1 || $dropoff > $stopCount || $pickup >= $dropoff) {
            throw new InvalidArgumentException(sprintf('Invalid ride span %d → %d on a route of %d stops: pickup must come before drop-off.', $pickup, $dropoff, $stopCount));
        }
    }
}
