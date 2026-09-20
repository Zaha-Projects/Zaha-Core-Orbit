<?php

namespace App\Support;

use IntlCalendar;
use RuntimeException;

class UmmAlQuraCalendar
{
    public const LOCALE = 'ar_SA@calendar=islamic-umalqura';

    public static function create(string $timezone): IntlCalendar
    {
        if (! class_exists(IntlCalendar::class)) {
            throw new RuntimeException('PHP ext-intl with ICU Islamic Umm al-Qura calendar support is required.');
        }

        $calendar = IntlCalendar::createInstance($timezone, self::LOCALE);
        if (! $calendar instanceof IntlCalendar) {
            throw new RuntimeException('ICU could not create the Islamic Umm al-Qura calendar.');
        }

        return $calendar;
    }
}
