<?php

namespace App\Services\Kpi;

use Carbon\CarbonImmutable;

/**
 * A calendar year or a single month. The comparison period is the previous year or the
 * previous month, which is always contiguous with the current one.
 */
class KpiPeriod
{
    public const MONTHS_AR = [
        1 => 'كانون الثاني', 2 => 'شباط', 3 => 'آذار', 4 => 'نيسان', 5 => 'أيار', 6 => 'حزيران',
        7 => 'تموز', 8 => 'آب', 9 => 'أيلول', 10 => 'تشرين الأول', 11 => 'تشرين الثاني', 12 => 'كانون الأول',
    ];

    public const MIN_YEAR = 2020;

    public int $year;

    public ?int $month;

    public CarbonImmutable $start;

    public CarbonImmutable $end;

    public function __construct(int $year, ?int $month = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->start = $month
            ? CarbonImmutable::create($year, $month, 1)->startOfDay()
            : CarbonImmutable::create($year, 1, 1)->startOfDay();
        $this->end = $month ? $this->start->endOfMonth()->startOfDay() : $this->start->endOfYear()->startOfDay();
    }

    public static function maxYear(): int
    {
        return (int) now()->year + 1;
    }

    /** @return array<int, int> */
    public static function selectableYears(): array
    {
        return range(self::maxYear(), self::MIN_YEAR);
    }

    public function previous(): self
    {
        if ($this->month) {
            $previous = $this->start->subMonthNoOverflow();

            return new self((int) $previous->year, (int) $previous->month);
        }

        return new self($this->year - 1);
    }

    /** Months of this period that have started by today (0 for a future period). */
    public function monthsElapsed(): int
    {
        $today = CarbonImmutable::today();
        if ($this->start->greaterThan($today)) {
            return 0;
        }
        if (! $this->month && $this->end->lessThan($today)) {
            return 12;
        }
        if ($this->month) {
            return 1;
        }

        return (int) $today->month;
    }

    /** End bound that also covers timestamp columns (whole last day). */
    public function endBound(): string
    {
        return $this->end->toDateString().' 23:59:59';
    }

    public function isMonthly(): bool
    {
        return $this->month !== null;
    }

    public function label(): string
    {
        return $this->month ? self::MONTHS_AR[$this->month].' '.$this->year : 'سنة '.$this->year;
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
