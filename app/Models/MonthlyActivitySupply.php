<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivitySupply extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'item_name',
        'status',
        'available',
        'provider_type',
        'provider_name',
    ];

    protected $casts = [
        'available' => 'boolean',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }
}
