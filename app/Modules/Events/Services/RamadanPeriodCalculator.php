<?php

namespace App\Modules\Events\Services;

use Carbon\CarbonImmutable;
use App\Support\UmmAlQuraCalendar;
use RuntimeException;

class RamadanPeriodCalculator
{
    public const SOURCE = 'intl_umm_al_qura';

    public function calculate(int $gregorianYear): array
    {
        if ($gregorianYear < 2020 || $gregorianYear > 2100) throw new RuntimeException('Gregorian year is outside the supported range.');
        $calendar = UmmAlQuraCalendar::create('UTC');

        $estimate = $gregorianYear - 579;
        foreach (range($estimate - 2, $estimate + 2) as $hijriYear) {
            $calendar->clear();
            $calendar->set($hijriYear, 8, 1, 0, 0, 0); // ICU month 8 is Ramadan (zero based).
            $start = CarbonImmutable::createFromTimestampUTC((int) floor($calendar->getTime() / 1000))->startOfDay();
            if ($start->year !== $gregorianYear) continue;
            $days = $calendar->getActualMaximum(\IntlCalendar::FIELD_DAY_OF_MONTH);

            return [
                'year' => $gregorianYear,
                'hijri_year' => $hijriYear,
                'suggested_start_date' => $start->toDateString(),
                'suggested_end_date' => $start->addDays($days - 1)->toDateString(),
                'calculation_source' => self::SOURCE,
            ];
        }
        throw new RuntimeException("No Umm al-Qura Ramadan was found in Gregorian year {$gregorianYear}.");
    }
}
