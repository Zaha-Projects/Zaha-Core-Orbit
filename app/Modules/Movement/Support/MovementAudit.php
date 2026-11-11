<?php

namespace App\Modules\Movement\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes Movement audit rows in the existing audit_logs pattern (plan §7.12, §15.6):
 * module = 'movement', action = '<entity>.<event>', old_values / new_values.
 */
final class MovementAudit
{
    private const HIDDEN = ['created_at', 'updated_at'];

    private function __construct()
    {
    }

    /** $userId defaults to the authenticated user; services pass their explicit actor. */
    public static function record(string $action, Model $entity, ?array $old = null, ?array $new = null, ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'module' => 'movement',
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'old_values' => $old === null ? null : self::clean($old),
            'new_values' => $new === null ? null : self::clean($new),
        ]);
    }

    /** Only the attributes that actually changed, as [old, new]. */
    public static function diff(array $before, Model $after): array
    {
        $changes = array_diff_key($after->getChanges(), array_flip(self::HIDDEN));

        return [array_intersect_key($before, $changes), $changes];
    }

    private static function clean(array $values): array
    {
        return array_diff_key($values, array_flip(self::HIDDEN));
    }
}
