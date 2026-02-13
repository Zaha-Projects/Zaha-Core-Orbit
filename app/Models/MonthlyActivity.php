<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'month',
        'day',
        'title',
        'proposed_date',
        'modified_proposed_date',
        'actual_date',
        'is_in_agenda',
        'agenda_event_id',
        'description',
        'has_official_attendance',
        'official_attendance_details',
        'needs_official_letters',
        'location_type',
        'location_details',
        'time_from',
        'time_to',
        'media_coverage',
        'status',
        'relations_officer_approval_status',
        'relations_manager_approval_status',
        'programs_officer_approval_status',
        'programs_manager_approval_status',
        'executive_approval_status',
        'lock_at',
        'is_official',
        'branch_id',
        'center_id',
        'created_by',
    ];

    protected $casts = [
        'is_in_agenda' => 'boolean',
        'has_official_attendance' => 'boolean',
        'needs_official_letters' => 'boolean',
        'proposed_date' => 'date',
        'modified_proposed_date' => 'date',
        'actual_date' => 'date',
        'lock_at' => 'datetime',
        'is_official' => 'boolean',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function supplies()
    {
        return $this->hasMany(MonthlyActivitySupply::class);
    }

    public function team()
    {
        return $this->hasMany(MonthlyActivityTeam::class);
    }

    public function attachments()
    {
        return $this->hasMany(MonthlyActivityAttachment::class);
    }

    public function approvals()
    {
        return $this->hasMany(MonthlyActivityApproval::class);
    }

    public function changeLogs()
    {
        return $this->hasMany(MonthlyActivityChangeLog::class);
    }

    public function attendance()
    {
        return $this->hasOne(ActivityAttendance::class);
    }

    public function donations()
    {
        return $this->hasMany(DonationCash::class);
    }
}
