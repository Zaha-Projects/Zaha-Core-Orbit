<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_date',
        'day_name',
        'driver_id',
        'vehicle_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'trip_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function segments()
    {
        return $this->hasMany(TripSegment::class);
    }

    public function rounds()
    {
        return $this->hasMany(TripRound::class);
    }
}
