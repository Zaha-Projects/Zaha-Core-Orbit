<?php

namespace App\Modules\Movement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** Plan §7.4: maintenance, leave and blocks. Exactly one of vehicle_id / driver_id (Revision 4: model guard, no CHECK). */
class Unavailability extends Model
{
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_LEAVE = 'leave';
    public const TYPE_INSPECTION = 'inspection';
    public const TYPE_OTHER = 'other';
    public const TYPES = [self::TYPE_MAINTENANCE, self::TYPE_LEAVE, self::TYPE_INSPECTION, self::TYPE_OTHER];

    protected $table = 'movement_unavailabilities';

    protected $fillable = ['vehicle_id', 'driver_id', 'type', 'starts_at', 'ends_at', 'reason', 'created_by'];

    /** Stored in UTC; convert with MovementTime for display and input. */
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $window) {
            if (($window->vehicle_id === null) === ($window->driver_id === null)) {
                throw new InvalidArgumentException('A movement unavailability must reference exactly one of vehicle_id or driver_id.');
            }
            if ($window->starts_at && $window->ends_at && ! $window->ends_at->gt($window->starts_at)) {
                throw new InvalidArgumentException('A movement unavailability must end after it starts.');
            }
        });
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
