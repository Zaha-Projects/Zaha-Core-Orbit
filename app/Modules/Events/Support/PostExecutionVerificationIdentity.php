<?php

namespace App\Modules\Events\Support;

final class PostExecutionVerificationIdentity
{
    public const LEGACY = 'App\\Models\\PostExecutionVerification';

    public const CANONICAL = 'App\\Modules\\Events\\Models\\PostExecutionVerification';

    /**
     * Identities that transition-aware audit readers must accept.
     *
     * @return list<string>
     */
    public static function acceptedTypes(): array
    {
        return [self::LEGACY, self::CANONICAL];
    }

    /**
     * Identity emitted before the Phase 2.8D namespace cutover.
     *
     * Phase 2.8D must change this single boundary to CANONICAL only when the
     * model moves and the rollback-safe deployment prerequisites are met.
     */
    public static function currentWriteType(): string
    {
        return self::LEGACY;
    }

    public static function accepts(string $type): bool
    {
        return in_array($type, self::acceptedTypes(), true);
    }
}
