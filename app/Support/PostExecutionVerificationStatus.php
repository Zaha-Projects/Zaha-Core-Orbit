<?php

namespace App\Support;

final class PostExecutionVerificationStatus
{
    public const PENDING = 'pending';
    public const CORRECT = 'correct';
    public const INCORRECT = 'incorrect';

    public static function values(): array
    {
        return [self::PENDING, self::CORRECT, self::INCORRECT];
    }
}
