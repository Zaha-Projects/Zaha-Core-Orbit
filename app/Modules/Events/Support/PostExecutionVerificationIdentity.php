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
     * Identity emitted after the PostExecutionVerification namespace cutover.
     */
    public static function currentWriteType(): string
    {
        return self::CANONICAL;
    }

    public static function accepts(string $type): bool
    {
        return in_array($type, self::acceptedTypes(), true);
    }
}
