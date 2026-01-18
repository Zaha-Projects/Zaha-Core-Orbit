<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendaEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'month',
        'day',
        'event_name',
        'event_category',
        'status',
        'created_by',
        'approved_by_relations_at',
        'approved_by_executive_at',
        'notes',
    ];

    protected $casts = [
        'approved_by_relations_at' => 'datetime',
        'approved_by_executive_at' => 'datetime',
    ];

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

    public function monthlyActivities()
    {
        return $this->hasMany(MonthlyActivity::class);
    }
}
