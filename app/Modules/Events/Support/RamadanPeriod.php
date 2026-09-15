<?php

namespace App\Modules\Events\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;

final class RamadanPeriod
{
    public const YEAR_KEY = 'ramadan_period_year';
    public const START_KEY = 'ramadan_period_start_date';
    public const END_KEY = 'ramadan_period_end_date';
    public const ACTIVE_KEY = 'ramadan_period_is_active';

    public static function active(): ?array
    {
        if (Setting::valueOf(self::ACTIVE_KEY, '0') !== '1') {
            return null;
        }

        $start = Setting::valueOf(self::START_KEY);
        $end = Setting::valueOf(self::END_KEY);
        if (! $start || ! $end) {
            return null;
        }

        $startDate = CarbonImmutable::parse($start)->startOfDay();
        $endDate = CarbonImmutable::parse($end)->startOfDay();

        return $startDate->lte($endDate) ? [
            'year' => (int) Setting::valueOf(self::YEAR_KEY, $startDate->format('Y')),
            'start' => $startDate,
            'end' => $endDate,
        ] : null;
    }

    public static function contains($date): bool
    {
        $period = self::active();
        if (! $period || ! $date) {
            return false;
        }

        $date = CarbonImmutable::parse($date)->startOfDay();

        return $date->between($period['start'], $period['end'], true);
    }
}
