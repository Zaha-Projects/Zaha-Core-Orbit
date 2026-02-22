<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_date',
        'event_day',
        'month',
        'day',
        'event_name',
        'department_id',
        'event_category_id',
        'event_category',
        'plan_type',
        'event_type',
        'status',
        'relations_approval_status',
        'executive_approval_status',
        'created_by',
        'approved_by_relations_at',
        'approved_by_executive_at',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'approved_by_relations_at' => 'datetime',
        'approved_by_executive_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function eventCategory()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets()
    {
        return $this->hasMany(AgendaEventTarget::class);
    }

    public function approvals()
    {
        return $this->hasMany(AgendaApproval::class);
    }

    public function participations()
    {
        return $this->hasMany(AgendaParticipation::class);
    }

    public function monthlyActivities()
    {
        return $this->hasMany(MonthlyActivity::class);
    }
}
