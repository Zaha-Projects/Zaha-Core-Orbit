<?php

namespace App\Support;

final class EvaluationVisibility
{
    public const BRANCH_ONLY = 'branch_only';
    public const AUTHORIZED_USERS = 'authorized_users';

    public static function values(): array
    {
        return [self::BRANCH_ONLY, self::AUTHORIZED_USERS];
    }
}
