<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'round_no',
        'location',
        'team',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
