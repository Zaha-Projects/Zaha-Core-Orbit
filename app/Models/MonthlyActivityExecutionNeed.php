<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityExecutionNeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'need_key',
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

