<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'day',
        'title',
        'proposed_date',
        'modified_proposed_date',
        'rescheduled_date',
        'reschedule_reason',
        'relations_approval_on_reschedule',
        'actual_date',
        'is_in_agenda',
        'responsible_party',
        'agenda_event_id',
        'description',
        'has_sponsor',
        'sponsor_name_title',
        'has_partners',
        'partner_1_name',
        'partner_1_role',
        'partner_2_name',
        'partner_2_role',
        'partner_3_name',
        'partner_3_role',
        'has_official_attendance',
        'official_attendance_details',
        'needs_official_letters',
        'letter_purpose',
        'location_type',
        'location_details',
        'time_from',
        'time_to',
        'execution_time',
        'target_group',
        'short_description',
        'volunteer_need',
        'audience_satisfaction_percent',
        'evaluation_score',
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
        'relations_approval_on_reschedule' => 'boolean',
        'has_sponsor' => 'boolean',
        'has_partners' => 'boolean',
        'proposed_date' => 'date',
        'modified_proposed_date' => 'date',
        'rescheduled_date' => 'date',
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

    public function sponsors()
    {
        return $this->hasMany(MonthlyActivitySponsor::class);
    }

    public function partners()
    {
        return $this->hasMany(MonthlyActivityPartner::class)->orderBy('sort_order');
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
