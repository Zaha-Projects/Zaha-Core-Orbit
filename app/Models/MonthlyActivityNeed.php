<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityNeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'need_key',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}

