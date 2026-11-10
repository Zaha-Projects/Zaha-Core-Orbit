<?php

namespace App\Modules\Movement\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Single conversion point between the Movement business timezone (Asia/Amman by default)
 * and UTC storage. Movement datetimes are stored in UTC and always handled as full
 * datetimes, so trips crossing midnight need no special case.
 */
class MovementTime
{
    public static function timezone(): string
    {
        return (string) config('movement.timezone', 'Asia/Amman');
    }

    /**
     * Interpret a local wall-clock value (e.g. "2026-11-10 23:30") in the Movement timezone and return it in UTC.
     * DateTime instances keep their own timezone and are converted as-is.
     */
    public static function toUtc(string|DateTimeInterface $localValue): CarbonImmutable
    {
        $moment = $localValue instanceof DateTimeInterface
            ? CarbonImmutable::instance($localValue)
            : CarbonImmutable::parse((string) $localValue, self::timezone());

        return $moment->setTimezone('UTC');
    }

    /**
     * Express a stored UTC value in the Movement timezone for display.
     * Strings without an explicit offset are interpreted as UTC.
     */
    public static function toLocal(string|DateTimeInterface $utcValue): CarbonImmutable
    {
        $moment = $utcValue instanceof DateTimeInterface
            ? CarbonImmutable::instance($utcValue)
            : CarbonImmutable::parse((string) $utcValue, 'UTC');

        return $moment->setTimezone(self::timezone());
    }

    /**
     * UTC bounds [start, end) of a local calendar day, for day-bucketed filters and calendars.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function localDayBoundsUtc(string|DateTimeInterface $localDate): array
    {
        $date = $localDate instanceof DateTimeInterface
            ? CarbonImmutable::instance($localDate)->setTimezone(self::timezone())->format('Y-m-d')
            : substr(trim((string) $localDate), 0, 10); // "Y-m-d" (a trailing time part is ignored)

        $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00', self::timezone());

        return [$start->setTimezone('UTC'), $start->addDay()->setTimezone('UTC')];
    }
}
