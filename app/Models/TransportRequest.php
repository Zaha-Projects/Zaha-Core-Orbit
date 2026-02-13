<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'requester_branch_id',
        'request_date',
        'day_name',
        'driver_id',
        'status',
        'movement_officer_notes',
        'general_notes',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'requester_branch_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function trips()
    {
        return $this->hasMany(TransportRequestTrip::class);
    }

    public function actions()
    {
        return $this->hasMany(TransportRequestAction::class);
    }

    public function feedback()
    {
        return $this->hasOne(TransportRequestFeedback::class);
    }
}
