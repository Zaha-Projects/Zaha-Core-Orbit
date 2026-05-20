<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityNeedTransport extends Model
{
    use HasFactory;

    protected $table = 'monthly_activity_need_transport';

    protected $fillable = [
        'monthly_activity_id',
        'is_required',
        'payload',
        'followup',
        'post_execution',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'payload' => 'array',
        'followup' => 'array',
        'post_execution' => 'array',
    ];
}
