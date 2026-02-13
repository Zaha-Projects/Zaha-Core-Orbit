<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportRequestAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_request_id',
        'action_type',
        'action_by',
        'action_at',
        'comment',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(TransportRequest::class, 'transport_request_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
