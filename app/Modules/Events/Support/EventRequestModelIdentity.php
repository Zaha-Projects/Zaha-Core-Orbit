<?php

namespace App\Modules\Events\Support;

use App\Models\AnnualAgendaDeleteRequest;
use App\Models\AnnualAgendaEditRequest;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;

final class EventRequestModelIdentity
{
    public const MONTHLY_EDIT_LEGACY = MonthlyPlanEditRequest::class;
    public const MONTHLY_EDIT_CANONICAL = 'App\\Modules\\Events\\Models\\MonthlyPlanEditRequest';
    public const MONTHLY_DELETE_LEGACY = MonthlyPlanDeleteRequest::class;
    public const MONTHLY_DELETE_CANONICAL = 'App\\Modules\\Events\\Models\\MonthlyPlanDeleteRequest';
    public const AGENDA_EDIT_LEGACY = AnnualAgendaEditRequest::class;
    public const AGENDA_EDIT_CANONICAL = 'App\\Modules\\Events\\Models\\AnnualAgendaEditRequest';
    public const AGENDA_DELETE_LEGACY = AnnualAgendaDeleteRequest::class;
    public const AGENDA_DELETE_CANONICAL = 'App\\Modules\\Events\\Models\\AnnualAgendaDeleteRequest';

    private const IDENTITIES = [
        self::MONTHLY_EDIT_LEGACY => self::MONTHLY_EDIT_CANONICAL,
        self::MONTHLY_DELETE_LEGACY => self::MONTHLY_DELETE_CANONICAL,
        self::AGENDA_EDIT_LEGACY => self::AGENDA_EDIT_CANONICAL,
        self::AGENDA_DELETE_LEGACY => self::AGENDA_DELETE_CANONICAL,
    ];

    /**
     * @return array<int, string>
     */
    public static function acceptedTypes(string $identity): array
    {
        $legacy = self::legacyFor($identity);

        return $legacy === null
            ? [$identity]
            : [$legacy, self::IDENTITIES[$legacy]];
    }

    public static function legacyFor(string $identity): ?string
    {
        if (array_key_exists($identity, self::IDENTITIES)) {
            return $identity;
        }

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
        return self::legacyFor($storedIdentity);
    }

    public static function currentWriteType(string $modelClass): string
    {
        return self::legacyFor($modelClass) ?? $modelClass;
    }

    public static function isCompatibleIdentity(string $identity): bool
    {
        return self::legacyFor($identity) !== null;
    }
}
