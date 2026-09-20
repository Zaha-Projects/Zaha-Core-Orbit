<?php

namespace App\Modules\Events\Support;

use App\Modules\Events\Models\AgendaEvent;
use App\Models\MonthlyActivity;

final class EventAggregateIdentity
{
    public const AGENDA_LEGACY = 'App\\Models\\AgendaEvent';
    public const AGENDA_CANONICAL = AgendaEvent::class;
    public const MONTHLY_ACTIVITY_LEGACY = MonthlyActivity::class;
    public const MONTHLY_ACTIVITY_CANONICAL = 'App\\Modules\\Events\\Models\\MonthlyActivity';

    private const IDENTITIES = [
        self::AGENDA_LEGACY => self::AGENDA_CANONICAL,
        self::MONTHLY_ACTIVITY_LEGACY => self::MONTHLY_ACTIVITY_CANONICAL,
    ];

    /**
     * @return array<int, string>
     */
    public static function acceptedTypes(string $identity): array
    {
        $legacy = self::legacyFor($identity);
        return $legacy === null ? [$identity] : [$legacy, self::IDENTITIES[$legacy]];
    }

    public static function legacyFor(string $identity): ?string
    {
        if (array_key_exists($identity, self::IDENTITIES)) return $identity;
        $legacy = array_search($identity, self::IDENTITIES, true);
        return $legacy === false ? null : $legacy;
    }

    public static function canonicalFor(string $identity): ?string
    {
        $legacy = self::legacyFor($identity);
        return $legacy === null ? null : self::IDENTITIES[$legacy];
    }

    public static function installedModelFor(string $storedIdentity): ?string
    {
        $legacy = self::legacyFor($storedIdentity);
        if ($legacy === self::AGENDA_LEGACY) return self::AGENDA_CANONICAL;
        if ($legacy === self::MONTHLY_ACTIVITY_LEGACY) return self::MONTHLY_ACTIVITY_LEGACY;
        return null;
    }

    public static function currentWriteType(string $modelClass): string
    {
        return self::installedModelFor($modelClass) ?? $modelClass;
    }

    public static function isCompatibleIdentity(string $identity): bool
    {
        return self::legacyFor($identity) !== null;
    }
}
