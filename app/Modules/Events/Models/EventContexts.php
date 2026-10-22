<?php

namespace App\Modules\Events\Models;

final class EventContexts
{
    public const AGENDA = 'agenda';

    public const MONTHLY_ACTIVITIES = 'monthly_activities';

    public static function all(): array
    {
        return [
            self::AGENDA,
            self::MONTHLY_ACTIVITIES,
        ];
    }
}
