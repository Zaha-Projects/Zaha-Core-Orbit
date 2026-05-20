<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityNeedFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'need_key',
        'followup',
        'post_execution',
    ];

    protected $casts = [
        'followup' => 'array',
        'post_execution' => 'array',
    ];
}

