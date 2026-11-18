<?php

namespace App\Modules\Movement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A scheduled vehicle run (plan §7.8, §15.3). Datetimes are UTC. Trips are never deleted (§7.12):
 * they end as `cancelled`, `completed` or `merged`.
 *
 * Phase 5 implements only draft → scheduled, scheduled → draft and draft/scheduled → cancelled (Revision 6).
 * Publishing (Phase 6), merging (Phase 8) and execution (Phase 10) extend TRANSITIONS in their phases.
 */
class Trip extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_MERGED = 'merged';

    /** Phase 5 transitions only (Revision 6). */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_SCHEDULED, self::STATUS_CANCELLED],
        self::STATUS_SCHEDULED => [self::STATUS_DRAFT, self::STATUS_CANCELLED],
    ];

    /** Trips whose blocked window makes the vehicle and driver busy (§13.1). */
    public const BLOCKING_STATUSES = [self::STATUS_SCHEDULED, self::STATUS_PUBLISHED, self::STATUS_IN_PROGRESS];

    /** Not yet started and not ended: planning may still change them (§13.6, Revision 6). */
    public const NON_TERMINAL_STATUSES = [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED];

    /** A request leg may be allocated only to these trips (§11.6). */
    public const ALLOCATABLE_STATUSES = self::NON_TERMINAL_STATUSES;

    /** Trips whose confirmed request bookings count as allocated, "A" in §8.5 and §11.6. */
    public const ALLOCATED_STATUSES = [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_PUBLISHED, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED];

    /** Trips whose confirmed request bookings count as scheduled, "S" in §8.5. */
    public const SCHEDULED_STATUSES = [self::STATUS_SCHEDULED, self::STATUS_PUBLISHED, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED];

    /** Bookings on these trips are never counted (§11.1). */
    public const ENDED_WITHOUT_SERVICE = [self::STATUS_CANCELLED, self::STATUS_MERGED];

    protected $table = 'movement_trips';

    protected $fillable = [
        'reference_no', 'status', 'vehicle_id', 'driver_id', 'seat_capacity', 'planned_departure_at', 'planned_arrival_at',
        'pre_trip_buffer_minutes', 'post_trip_buffer_minutes', 'blocked_from', 'blocked_until', 'paired_trip_id',
        'vehicle_waits_between', 'driver_waits_between', 'allows_join', 'booking_closes_at', 'estimated_distance_m',
        'estimated_duration_s', 'route_source', 'merged_into_trip_id', 'published_at', 'published_by', 'cancelled_at',
        'cancelled_by', 'cancellation_reason', 'actual_departure_at', 'actual_arrival_at', 'driver_acknowledged_at', 'created_by',
    ];

    protected $casts = [
        'seat_capacity' => 'integer',
        'pre_trip_buffer_minutes' => 'integer',
        'post_trip_buffer_minutes' => 'integer',
        'planned_departure_at' => 'datetime',
        'planned_arrival_at' => 'datetime',
        'blocked_from' => 'datetime',
        'blocked_until' => 'datetime',
        'vehicle_waits_between' => 'boolean',
        'driver_waits_between' => 'boolean',
        'allows_join' => 'boolean',
        'booking_closes_at' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'actual_departure_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
        'driver_acknowledged_at' => 'datetime',
    ];

    public function vehicle() { return $this->belongsTo(Vehicle::class)->withTrashed(); }
    public function driver() { return $this->belongsTo(Driver::class)->withTrashed(); }
    public function stops() { return $this->hasMany(TripStop::class, 'movement_trip_id')->orderBy('sequence'); }
    public function bookings() { return $this->hasMany(Booking::class, 'movement_trip_id'); }
    public function pairedTrip() { return $this->belongsTo(self::class, 'paired_trip_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isNonTerminal(): bool
    {
        return in_array($this->status, self::NON_TERMINAL_STATUSES, true);
    }

    public function hasWindow(): bool
    {
        return $this->blocked_from !== null && $this->blocked_until !== null;
    }
}
