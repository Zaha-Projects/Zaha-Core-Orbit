<?php

namespace App\Modules\Events\Support;

use App\Modules\Events\Models\AnnualAgendaDeleteRequest;
use App\Modules\Events\Models\AnnualAgendaEditRequest;
use App\Modules\Events\Models\MonthlyPlanDeleteRequest;
use App\Modules\Events\Models\MonthlyPlanEditRequest;

final class EventRequestModelIdentity
{
    public const MONTHLY_EDIT_LEGACY = 'App\\Models\\MonthlyPlanEditRequest';
    public const MONTHLY_EDIT_CANONICAL = MonthlyPlanEditRequest::class;
    public const MONTHLY_DELETE_LEGACY = 'App\\Models\\MonthlyPlanDeleteRequest';
    public const MONTHLY_DELETE_CANONICAL = MonthlyPlanDeleteRequest::class;
    public const AGENDA_EDIT_LEGACY = 'App\\Models\\AnnualAgendaEditRequest';
    public const AGENDA_EDIT_CANONICAL = AnnualAgendaEditRequest::class;
    public const AGENDA_DELETE_LEGACY = 'App\\Models\\AnnualAgendaDeleteRequest';
    public const AGENDA_DELETE_CANONICAL = AnnualAgendaDeleteRequest::class;

    private const IDENTITIES = [
        self::MONTHLY_EDIT_LEGACY => self::MONTHLY_EDIT_CANONICAL,
        self::MONTHLY_DELETE_LEGACY => self::MONTHLY_DELETE_CANONICAL,
        self::AGENDA_EDIT_LEGACY => self::AGENDA_EDIT_CANONICAL,
        self::AGENDA_DELETE_LEGACY => self::AGENDA_DELETE_CANONICAL,
    ];

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
        if ($legacy === null) return null;
        return self::IDENTITIES[$legacy];
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
