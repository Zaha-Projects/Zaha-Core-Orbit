<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityNeedDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'need_key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}

