<?php

namespace App\Modules\Events\Models;

use App\Models\MonthlyActivity;
use InvalidArgumentException;

final class EventSubjectTypes
{
    public const MONTHLY_ACTIVITY = 'monthly_activity';

    public const RAMADAN_IFTAR = 'ramadan_iftar';

    public static function reserved(): array
    {
        return [
            self::MONTHLY_ACTIVITY,
            self::RAMADAN_IFTAR,
        ];
    }

    /** @return array<string, class-string> */
    public static function registeredModels(): array
    {
        return [
            self::MONTHLY_ACTIVITY => MonthlyActivity::class,
            self::RAMADAN_IFTAR => RamadanIftar::class,
        ];
    }

    /** @return class-string */
    public static function modelFor(string $type): string
    {
        $model = self::registeredModels()[$type] ?? null;

        if ($model === null) {
            throw new InvalidArgumentException("Unsupported event subject type [{$type}].");
        }

        return $model;
    }
}
