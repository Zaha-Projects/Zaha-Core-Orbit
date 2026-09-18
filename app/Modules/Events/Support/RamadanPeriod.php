<?php

namespace App\Modules\Events\Support;

use App\Models\Setting;
use App\Modules\Events\Models\RamadanPeriod as Period;
use Carbon\CarbonImmutable;

final class RamadanPeriod
{
    public const DEFAULT_YEAR_KEY = 'ramadan_default_year';

    public static function defaultYear(): int
    {
        return (int) Setting::valueOf(self::DEFAULT_YEAR_KEY, Setting::valueOf(self::YEAR_KEY, now()->year));
    }

    public const YEAR_KEY = 'ramadan_period_year';
    public const START_KEY = 'ramadan_period_start_date';
    public const END_KEY = 'ramadan_period_end_date';
    public const ACTIVE_KEY = 'ramadan_period_is_active';

    public static function active(): ?array
    {
        $period = Period::query()->active()
            ->where('year', self::defaultYear())->first();

        return $period ? [
            'year' => $period->year,
            'start' => $period->start_date,
            'end' => $period->end_date,
        ] : null;
    }

    public static function contains($date): bool
    {
        if (! $date) {
            return false;
        }

        try {
            $date = CarbonImmutable::parse($date)->toDateString();
        } catch (\Throwable $exception) {
            return false;
        }

        return Period::query()->active()->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)->exists();
    }
}
