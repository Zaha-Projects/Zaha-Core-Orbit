<?php

namespace App\Services\Kpi;

class KpiFormatter
{
    public const UNAVAILABLE = 'بيانات غير متوفرة';

    public function format($value, string $unit): string
    {
        if ($value === null) {
            return self::UNAVAILABLE;
        }

        switch ($unit) {
            case 'percentage':
                return $this->number((float) $value, 1).'%';
            case 'days':
                return $this->number((float) $value, 1).' يوم';
            case 'hours':
                return $this->number((float) $value, 1).' ساعة';
            case 'score':
                return $this->number((float) $value, 2).' / 10';
            default:
                return number_format((int) $value);
        }
    }

    /** Signed change for trend badges; percentage KPIs change in percentage points. */
    public function formatDelta(float $delta, string $unit): string
    {
        $sign = $delta > 0 ? '+' : ($delta < 0 ? '−' : '');
        $magnitude = abs($delta);

        return $unit === 'percentage'
            ? $sign.$this->number($magnitude, 1).' نقطة'
            : $sign.$this->format($magnitude, $unit);
    }

    private function number(float $value, int $decimals): string
    {
        $formatted = number_format($value, $decimals);

        return str_contains($formatted, '.') ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }
}
