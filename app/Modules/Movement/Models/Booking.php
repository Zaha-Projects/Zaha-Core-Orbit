<?php

namespace App\Modules\Movement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A reservation of seats on a ride span (plan §7.10, §15.4). Bookings are never deleted (§7.12).
 * Phase 5 creates only request bookings: source 'request', status 'confirmed', through
 * TripSchedulingService::allocateRequestLeg(). Join and manager bookings arrive in Phase 6.
 * active_join_key is a database-generated column and is never written by the application.
 */
class Booking extends Model
{
    public const SOURCE_REQUEST = 'request';
    public const SOURCE_JOIN = 'join';
    public const SOURCE_MANAGER = 'manager';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    /** Statuses that occupy seats (§11.1): pending never holds seats. */
    public const COUNTING_STATUSES = [self::STATUS_CONFIRMED, self::STATUS_NO_SHOW];

    /** Statuses a cancellation applies to (§8.4, §15.4). */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    protected $table = 'movement_bookings';

    protected $fillable = [
        'movement_trip_id', 'request_leg_id', 'source', 'booked_by_user_id', 'branch_id', 'context_type', 'context_id', 'context_label',
        'pickup_stop_id', 'dropoff_stop_id', 'passenger_count', 'contact_phone', 'notes', 'status', 'client_token',
        'decided_by', 'decided_at', 'decision_comment', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'passenger_count' => 'integer',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function trip() { return $this->belongsTo(Trip::class, 'movement_trip_id'); }
    public function requestLeg() { return $this->belongsTo(MovementRequestLeg::class, 'request_leg_id'); }
    public function pickupStop() { return $this->belongsTo(TripStop::class, 'pickup_stop_id'); }
    public function dropoffStop() { return $this->belongsTo(TripStop::class, 'dropoff_stop_id'); }
    public function bookedBy() { return $this->belongsTo(User::class, 'booked_by_user_id'); }

    public function scopeCounting(Builder $query): Builder
    {
        return $query->whereIn('status', self::COUNTING_STATUSES);
    }
}
