<?php

namespace App\Support;

use Carbon\CarbonInterface;
use IntlDateFormatter;
use RuntimeException;

class HijriDateFormatter
{
    /** Display-only Umm al-Qura date; operational Ramadan dates remain administrator-approved. */
    public function format(CarbonInterface $date): string
    {
        if (! class_exists(IntlDateFormatter::class)) {
            throw new RuntimeException('PHP ext-intl is required to display the Hijri date.');
        }

        $formatter = new IntlDateFormatter(
            UmmAlQuraCalendar::LOCALE,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            config('app.timezone'),
            IntlDateFormatter::TRADITIONAL,
            'd MMMM y'
        );

        return $formatter->format($date) . ' هـ';
    }
}
