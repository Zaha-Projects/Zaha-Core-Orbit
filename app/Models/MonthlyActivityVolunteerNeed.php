<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityVolunteerNeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'volunteer_need',
        'required_volunteers',
        'volunteer_age_range',
        'volunteer_gender',
        'volunteer_tasks_summary',
        'volunteers_required',
        'volunteers_count',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }
}
