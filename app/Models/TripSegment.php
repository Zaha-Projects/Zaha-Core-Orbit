<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'segment_no',
        'location',
        'team_companion',
        'depart_time',
        'return_time',
        'notes',
    ];

    protected $casts = [
        'depart_time' => 'datetime:H:i',
        'return_time' => 'datetime:H:i',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
