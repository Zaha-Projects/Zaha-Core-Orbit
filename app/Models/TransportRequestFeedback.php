<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportRequestFeedback extends Model
{
    use HasFactory;

    protected $table = 'transport_request_feedback';

    protected $fillable = [
        'transport_request_id',
        'submitted_by',
        'punctuality_score',
        'cleanliness_score',
        'driver_behavior_score',
        'overall_score',
        'comment',
    ];

    public function request()
    {
        return $this->belongsTo(TransportRequest::class, 'transport_request_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
