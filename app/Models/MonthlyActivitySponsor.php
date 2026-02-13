<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivitySponsor extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'name',
        'title',
        'is_official',
    ];

    protected $casts = [
        'is_official' => 'boolean',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }
}
