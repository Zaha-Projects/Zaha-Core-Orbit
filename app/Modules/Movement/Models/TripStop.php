<?php

namespace App\Modules\Movement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An ordered stop of a trip (plan §7.9). Name, address, coordinates and Maps link are snapshots taken when the
 * stop is added, so later edits to a saved location never rewrite history. Times are UTC.
 * running_seconds_from_previous is the free-flow running time of the section ending here (§9.4).
 */
class TripStop extends Model
{
    protected $table = 'movement_trip_stops';

    protected $fillable = [
        'movement_trip_id', 'sequence', 'location_id', 'name', 'address', 'latitude', 'longitude', 'maps_url',
        'planned_arrival_at', 'planned_departure_at', 'running_seconds_from_previous', 'distance_m_from_previous',
        'allows_pickup', 'allows_drop_off', 'actual_arrival_at', 'actual_departure_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'planned_arrival_at' => 'datetime',
        'planned_departure_at' => 'datetime',
        'running_seconds_from_previous' => 'integer',
        'distance_m_from_previous' => 'integer',
        'allows_pickup' => 'boolean',
        'allows_drop_off' => 'boolean',
        'actual_arrival_at' => 'datetime',
        'actual_departure_at' => 'datetime',
    ];

    public function trip() { return $this->belongsTo(Trip::class, 'movement_trip_id'); }
    public function location() { return $this->belongsTo(Location::class)->withTrashed(); }
}
