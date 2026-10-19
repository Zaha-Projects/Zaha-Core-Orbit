<?php

namespace App\Services\Kpi\Support;

use Illuminate\Support\Facades\DB;

/**
 * Small, driver-aware SQL fragment helpers for KPI aggregates.
 *
 * Fragments are composed from trusted constants, integer ids and Carbon dates only;
 * request input never reaches these helpers.
 */
class KpiSql
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    /** Count rows matching a condition. */
    public static function countIf(string $condition): string
    {
        return 'SUM(CASE WHEN '.$condition.' THEN 1 ELSE 0 END)';
    }

    /** Sum an expression over matching rows (0 when none match). */
    public static function sumIf(string $condition, string $expression): string
    {
        return 'COALESCE(SUM(CASE WHEN '.$condition.' THEN '.$expression.' END), 0)';
    }

    /** Count matching rows whose expression is not null (pairs with sumIf for averages). */
    public static function countValue(string $condition, string $expression): string
    {
        return 'COUNT(CASE WHEN '.$condition.' THEN '.$expression.' END)';
    }

    /** Whole days from $from to $to (positive when $to is later). */
    public static function daysBetween(string $from, string $to): string
    {
        switch (self::driver()) {
            case 'sqlite':
                return '(julianday('.$to.') - julianday('.$from.'))';
            case 'pgsql':
                return '(CAST('.$to.' AS DATE) - CAST('.$from.' AS DATE))';
            default:
                return 'DATEDIFF('.$to.', '.$from.')';
        }
    }

    /** Hours from $from to $to. */
    public static function hoursBetween(string $from, string $to): string
    {
        switch (self::driver()) {
            case 'sqlite':
                return '((julianday('.$to.') - julianday('.$from.')) * 24)';
            case 'pgsql':
                return '(EXTRACT(EPOCH FROM ('.$to.' - '.$from.')) / 3600)';
            default:
                return 'TIMESTAMPDIFF(HOUR, '.$from.', '.$to.')';
        }
    }

    public static function dateOf(string $expression): string
    {
        return self::driver() === 'sqlite' ? 'date('.$expression.')' : 'CAST('.$expression.' AS DATE)';
    }

    public static function month(string $expression): string
    {
        switch (self::driver()) {
            case 'sqlite':
                return "CAST(strftime('%m', ".$expression.') AS INTEGER)';
            case 'pgsql':
                return 'EXTRACT(MONTH FROM '.$expression.')';
            default:
                return 'MONTH('.$expression.')';
        }
    }

    /**
     * Number of elements in a JSON array column whose $field equals $value.
     * Only available on MySQL/MariaDB (JSON_SEARCH); null elsewhere so the KPI reports
     * itself unavailable instead of returning a fake 0.
     */
    public static function jsonArrayFieldCount(string $column, string $field, string $value): ?string
    {
        if (! in_array(self::driver(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        return 'COALESCE(JSON_LENGTH(JSON_SEARCH('.$column.', \'all\', '.self::literal($value).', NULL, \'$[*].'.$field.'\')), 0)';
    }

    public static function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    public static function literalList(array $values): string
    {
        return implode(', ', array_map([self::class, 'literal'], $values));
    }

    public static function intList(array $values): string
    {
        return implode(', ', array_map('intval', $values));
    }
}
