<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'name',
        'role',
        'contact_info',
        'sort_order',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }
}
