<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportRequestTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_request_id',
        'trip_no',
        'vehicle_id',
        'destination',
        'accompanying_team',
        'departure_time',
        'return_time',
    ];

    public function request()
    {
        return $this->belongsTo(TransportRequest::class, 'transport_request_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
