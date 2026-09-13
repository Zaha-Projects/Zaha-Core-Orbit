<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_other',
        'is_active',
        'is_monthly_activity',
        'is_ramadan_iftar',
        'sort_order',
    ];

    protected $casts = [
        'is_other' => 'boolean',
        'is_active' => 'boolean',
        'is_monthly_activity' => 'boolean',
        'is_ramadan_iftar' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMonthlyActivities($query)
    {
        return $query->where('is_monthly_activity', true);
    }

    public function scopeForRamadanIftars($query)
    {
        return $query->where('is_ramadan_iftar', true);
    }
}
