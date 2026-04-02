<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyKpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'branch_id',
        'planned_activities_count',
        'unplanned_activities_count',
        'modification_rate_percent',
        'plan_commitment_percent',
        'mobilization_efficiency_percent',
        'branch_monthly_score',
        'followup_commitment_score',
        'notes',
        'created_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
