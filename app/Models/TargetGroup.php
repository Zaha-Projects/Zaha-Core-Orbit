<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_other', 'is_active', 'sort_order'];

    protected $casts = [
        'is_other' => 'boolean',
        'is_active' => 'boolean',
    ];
}
