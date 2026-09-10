<?php

namespace App\Modules\Events\Models;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RamadanIftar extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const EXECUTION_STATUS_PLANNED = 'planned';

    public const LOCATION_INSIDE_CENTER = 'inside_center';
    public const LOCATION_OUTSIDE_CENTER = 'outside_center';

    public const HOST_ASSOCIATION = 'association';
    public const HOST_CENTER = 'center';
    public const HOST_LOCAL_COMMUNITY = 'local_community';

    protected $fillable = [
        'agenda_event_id',
        'branch_id',
        'title',
        'description',
        'relations_officer_id',
        'created_by',
        'planned_date',
        'actual_date',
        'time_from',
        'time_to',
        'location_type',
        'location_name',
        'address',
        'google_maps_url',
        'contact_name',
        'contact_phone',
        'supporting_entity_name',
        'host_type',
        'community_organization_id',
        'local_community_id',
        'mobilization_method_id',
        'mobilization_method_other',
        'planned_meals_count',
        'actual_meals_count',
        'expected_attendance',
        'actual_attendance',
        'status',
        'execution_status',
        'version_number',
        'parent_version_id',
        'guidance_accepted_at',
        'submitted_at',
        'approved_at',
        'closed_at',
    ];

    protected $casts = [
        'agenda_event_id' => 'integer',
        'branch_id' => 'integer',
        'relations_officer_id' => 'integer',
        'created_by' => 'integer',
        'planned_date' => 'date',
        'actual_date' => 'date',
        'planned_meals_count' => 'integer',
        'actual_meals_count' => 'integer',
        'expected_attendance' => 'integer',
        'actual_attendance' => 'integer',
        'version_number' => 'integer',
        'guidance_accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function relationsOfficer()
    {
        return $this->belongsTo(User::class, 'relations_officer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function communityOrganization()
    {
        return $this->belongsTo(CommunityOrganization::class);
    }

    public function localCommunity()
    {
        return $this->belongsTo(LocalCommunity::class);
    }

    public function mobilizationMethod()
    {
        return $this->belongsTo(MobilizationMethod::class);
    }

    public function parentVersion()
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function versions()
    {
        return $this->hasMany(self::class, 'parent_version_id');
    }

    public function targetGroupSelections()
    {
        return $this->hasMany(SubjectTargetGroup::class, 'subject_id')
            ->where('subject_type', EventSubjectTypes::RAMADAN_IFTAR);
    }

    public function attendees()
    {
        return $this->hasMany(RamadanIftarAttendee::class);
    }

    public function meals()
    {
        return $this->hasMany(RamadanIftarMeal::class);
    }

    public function gifts()
    {
        return $this->hasMany(RamadanIftarGift::class);
    }

    public function programSegments()
    {
        return $this->hasMany(RamadanIftarProgramSegment::class)->orderBy('sort_order');
    }
}
