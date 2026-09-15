<?php

namespace App\Modules\Events\Support;

use App\Modules\Events\Models\AgendaEvent;

final class EventAggregateIdentity
{
    public const AGENDA_LEGACY = 'App\\Models\\AgendaEvent';
    public const AGENDA_CANONICAL = AgendaEvent::class;

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
        return self::isAgendaIdentity($storedIdentity) ? self::AGENDA_CANONICAL : null;
    }

    public static function currentWriteType(string $modelClass): string
    {
        return self::isAgendaIdentity($modelClass) ? self::AGENDA_CANONICAL : $modelClass;
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
