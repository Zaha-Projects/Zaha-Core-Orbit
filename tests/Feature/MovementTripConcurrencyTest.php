<?php

namespace Tests\Feature;

use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\Booking;
use App\Modules\Movement\Models\Trip;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\MovementTripFixtures;
use Tests\TestCase;

/**
 * Concurrency (plan §11.5–11.6, §13.5; Phase 5 acceptance) with two real MySQL connections:
 * connection A holds its row locks in an open transaction; connection B (lock-wait timeout 1 s) must block on them
 * instead of acting on stale data, and after A finishes B's re-validation sees A's committed result.
 *
 * Runs migrate:fresh and commits rows, so it needs a disposable MySQL database (MOVEMENT_DISPOSABLE_DB=true).
 */
class MovementTripConcurrencyTest extends TestCase
{
    use MovementTripFixtures;

    private const SECOND = 'movement_concurrency_b';

    private string $first;

    protected function setUp(): void
    {
        parent::setUp();

        $this->first = (string) config('database.default');
        if (! filter_var(env('MOVEMENT_DISPOSABLE_DB', false), FILTER_VALIDATE_BOOLEAN) || config("database.connections.{$this->first}.driver") !== 'mysql') {
            $this->markTestSkipped('Needs a disposable MySQL database (MOVEMENT_DISPOSABLE_DB=true), e.g. the test container.');
        }

        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        config(['database.connections.'.self::SECOND => config("database.connections.{$this->first}")]);
        DB::purge(self::SECOND);
        DB::connection(self::SECOND)->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->first ?? config('database.default'));
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge(self::SECOND);
        RefreshDatabaseState::$migrated = false; // committed rows: the next RefreshDatabase class rebuilds the schema

        parent::tearDown();
    }

    /** Run $callback with connection B as the default connection (Eloquent and the services follow it). */
    private function onSecond(callable $callback): mixed
    {
        DB::setDefaultConnection(self::SECOND);
        try {
            return $callback();
        } finally {
            DB::setDefaultConnection($this->first);
        }
    }

    private function assertBlocked(callable $callback): void
    {
        try {
            $this->onSecond($callback);
        } catch (QueryException $e) {
            $this->assertSame(1205, (int) ($e->errorInfo[1] ?? 0), 'B must wait for A\'s row lock (lock wait timeout).');

            return;
        }

        $this->fail('Connection B was not blocked by connection A\'s lock.');
    }

    private function assertRefused(callable $callback, string $message): void
    {
        try {
            $this->onSecond($callback);
        } catch (IllegalMovementTransition $e) {
            $this->assertStringContainsString($message, $e->getMessage());

            return;
        }

        $this->fail('Connection B should have been refused.');
    }

    public function test_two_concurrent_allocations_of_one_leg_never_exceed_its_passengers(): void
    {
        $leg = $this->approvedLeg(10, '2030-01-15 12:00');
        $first = $this->tripWithStops(['A', 'B'], 10);
        $second = $this->tripWithStops(['A', 'B'], 10);
        $allocate = fn (Trip $trip, int $pax) => $this->trips()->allocateRequestLeg($leg, $trip, $this->stopAt($trip, 1), $this->stopAt($trip, 2), $pax, $this->actor());

        DB::beginTransaction();
        $allocate($first, 8); // A holds the leg lock (§11.6 step 1)

        $this->assertBlocked(fn () => $allocate($second, 8));

        DB::commit();
        $this->assertRefused(fn () => $allocate($second, 8), __('movement.trips.errors.leg_over_allocated', ['remaining' => 2]));
        $this->onSecond(fn () => $allocate($second, 2));

        $this->assertSame(10, (int) Booking::query()->where('request_leg_id', $leg->id)->where('status', Booking::STATUS_CONFIRMED)->sum('passenger_count'));
    }

    public function test_two_concurrent_schedules_of_one_vehicle_never_overlap(): void
    {
        $vehicle = $this->vehicle();
        $early = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 08:00', [60]);
        $late = $this->timed($this->tripWithStops(['A', 'B']), '2030-01-15 08:30', [60]);
        $this->trips()->assign($early, $this->actor(), $vehicle->id, $this->driver()->id); // drafts block nothing yet
        $this->trips()->assign($late, $this->actor(), $vehicle->id, $this->driver()->id);

        DB::beginTransaction();
        $this->trips()->schedule($early, $this->actor()); // A holds the vehicle lock (§13.5 step 2)

        $this->assertBlocked(fn () => $this->trips()->schedule($late, $this->actor()));

        DB::commit();
        $this->assertRefused(fn () => $this->trips()->schedule($late, $this->actor()), $early->reference_no);
        $this->assertSame([Trip::STATUS_SCHEDULED, Trip::STATUS_DRAFT], [$early->fresh()->status, $late->fresh()->status]);
    }

    public function test_deallocation_and_allocation_of_one_leg_are_serialised(): void
    {
        $leg = $this->approvedLeg(10, '2030-01-15 12:00');
        $first = $this->tripWithStops(['A', 'B'], 10);
        $second = $this->tripWithStops(['A', 'B'], 10);
        $booking = $this->trips()->allocateRequestLeg($leg, $first, $this->stopAt($first, 1), $this->stopAt($first, 2), 10, $this->actor());
        $allocateSecond = fn () => $this->trips()->allocateRequestLeg($leg, $second, $this->stopAt($second, 1), $this->stopAt($second, 2), 10, $this->actor());

        // A releases the booking but rolls back: B must neither proceed early nor see the release.
        DB::beginTransaction();
        $this->trips()->deallocate($booking, $this->actor());
        $this->assertBlocked($allocateSecond);
        DB::rollBack();
        $this->assertRefused($allocateSecond, __('movement.trips.errors.leg_over_allocated', ['remaining' => 0]));

        // A releases and commits: now B can move the passengers.
        DB::beginTransaction();
        $this->trips()->reallocate($booking, $second, $this->stopAt($second, 1), $this->stopAt($second, 2), 4, $this->actor());
        $this->assertBlocked($allocateSecond);
        DB::commit();
        $this->assertRefused($allocateSecond, __('movement.trips.errors.leg_over_allocated', ['remaining' => 0]));
        $this->onSecond(fn () => $this->trips()->deallocate($booking->fresh(), $this->actor(), 2));
        $this->onSecond(fn () => $this->trips()->allocateRequestLeg($leg, $second, $this->stopAt($second, 1), $this->stopAt($second, 2), 2, $this->actor()));

        $this->assertSame(10, (int) Booking::query()->where('request_leg_id', $leg->id)->where('status', Booking::STATUS_CONFIRMED)->sum('passenger_count'));
    }
}
