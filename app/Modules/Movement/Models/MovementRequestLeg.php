<?php

namespace App\Modules\Movement\Models;

use Illuminate\Database\Eloquent\Model;

/** Outbound or return leg of a movement request (plan §7.7). Datetimes are UTC; travel_date is the local date. */
class MovementRequestLeg extends Model
{
    public const TYPE_OUTBOUND = 'outbound';
    public const TYPE_RETURN = 'return';
    public const TYPES = [self::TYPE_OUTBOUND, self::TYPE_RETURN];

    protected $table = 'movement_request_legs';

    protected $fillable = [
        'movement_request_id', 'leg_type', 'origin_location_id', 'destination_location_id', 'origin_text', 'destination_text',
        'origin_maps_url', 'destination_maps_url', 'passenger_count', 'travel_date', 'required_arrival_at', 'preferred_departure_at', 'notes',
    ];

    protected $casts = [
        'passenger_count' => 'integer',
        'travel_date' => 'date',
        'required_arrival_at' => 'datetime',
        'preferred_departure_at' => 'datetime',
    ];

    public function request() { return $this->belongsTo(MovementRequest::class, 'movement_request_id'); }
    public function originLocation() { return $this->belongsTo(Location::class, 'origin_location_id')->withTrashed(); }
    public function destinationLocation() { return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed(); }

    public function originLabel(): string
    {
        return $this->originLocation?->display_name ?: (string) $this->origin_text;
    }

    public function destinationLabel(): string
    {
        return $this->destinationLocation?->display_name ?: (string) $this->destination_text;
    }
}
