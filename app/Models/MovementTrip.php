<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_day_id',
        'vehicle_id',
        'destination',
        'team',
        'departure_time',
        'return_time',
    ];

    public function movementDay()
    {
        return $this->belongsTo(MovementDay::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
