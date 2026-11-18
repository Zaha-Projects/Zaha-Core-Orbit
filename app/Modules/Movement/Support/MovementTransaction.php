<?php

namespace App\Modules\Movement\Support;

use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Models\Trip;
use App\Modules\Movement\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe Movement writes (plan §5.2, §11.5–11.6, §13.5).
 *
 * - Row locks are always taken in the global order: request legs, trips, vehicles, drivers, each group by
 *   ascending id. Bookings and stops are written only while their trip is locked.
 * - A top-level transaction runs at READ COMMITTED on MySQL, so every read taken after a lock is granted sees the
 *   latest committed rows (under REPEATABLE READ a plain read could use a snapshot older than the lock).
 * - A deadlock (1213) or lock-wait timeout (1205) retries the whole callback once (§5.2).
 *
 * Inside an outer transaction (e.g. a test's RefreshDatabase transaction) the callback simply joins it.
 */
final class MovementTransaction
{
    private function __construct()
    {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(callable $callback, int $attempts = 2)
    {
        for ($attempt = 1; ; $attempt++) {
            $topLevel = DB::transactionLevel() === 0;
            if ($topLevel && DB::getDriverName() === 'mysql') {
                DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED'); // applies to the next transaction only
            }

            try {
                return DB::transaction(fn () => $callback());
            } catch (QueryException $e) {
                if (! $topLevel || $attempt >= $attempts || ! self::isConcurrencyError($e)) {
                    throw $e;
                }
            }
        }
    }

    /** @return Collection<int, MovementRequestLeg> keyed by id */
    public static function lockLegs(iterable $ids): Collection
    {
        $ids = self::ids($ids);

        return $ids === [] ? collect() : MovementRequestLeg::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /** @return Collection<int, Trip> keyed by id */
    public static function lockTrips(iterable $ids): Collection
    {
        $ids = self::ids($ids);

        return $ids === [] ? collect() : Trip::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /** @return Collection<int, Vehicle> keyed by id; soft-deleted rows included, so callers can refuse them */
    public static function lockVehicles(iterable $ids): Collection
    {
        $ids = self::ids($ids);

        return $ids === [] ? collect() : Vehicle::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /** @return Collection<int, Driver> keyed by id; soft-deleted rows included */
    public static function lockDrivers(iterable $ids): Collection
    {
        $ids = self::ids($ids);

        return $ids === [] ? collect() : Driver::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    private static function isConcurrencyError(QueryException $e): bool
    {
        return in_array((int) ($e->errorInfo[1] ?? 0), [1205, 1213], true);
    }

    /** @return list<int> unique, ascending, without nulls */
    private static function ids(iterable $ids): array
    {
        $list = [];
        foreach ($ids as $id) {
            if ($id !== null) {
                $list[(int) $id] = (int) $id;
            }
        }
        ksort($list);

        return array_values($list);
    }
}
