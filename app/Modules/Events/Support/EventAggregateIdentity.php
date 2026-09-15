<?php

namespace App\Modules\Events\Support;

use App\Models\AgendaEvent;

final class EventAggregateIdentity
{
    public const AGENDA_LEGACY = AgendaEvent::class;
    public const AGENDA_CANONICAL = 'App\\Modules\\Events\\Models\\AgendaEvent';

    /**
     * @return array<int, string>
     */
    public static function acceptedTypes(string $identity): array
    {
        return self::isAgendaIdentity($identity)
            ? [self::AGENDA_LEGACY, self::AGENDA_CANONICAL]
            : [$identity];
    }

    public static function legacyFor(string $identity): ?string
    {
        return self::isAgendaIdentity($identity) ? self::AGENDA_LEGACY : null;
    }

    public static function canonicalFor(string $identity): ?string
    {
        return self::isAgendaIdentity($identity) ? self::AGENDA_CANONICAL : null;
    }

    public static function installedModelFor(string $storedIdentity): ?string
    {
        return self::legacyFor($storedIdentity);
    }

    public static function currentWriteType(string $modelClass): string
    {
        return self::legacyFor($modelClass) ?? $modelClass;
    }

    public static function isCompatibleIdentity(string $identity): bool
    {
        return self::isAgendaIdentity($identity);
    }

    private static function isAgendaIdentity(string $identity): bool
    {
        return $identity === self::AGENDA_LEGACY || $identity === self::AGENDA_CANONICAL;
    }
}
