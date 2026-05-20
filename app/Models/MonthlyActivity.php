<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\WorkflowInstance;

class MonthlyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'day',
        'activity_date',
        'title',
        'proposed_date',
        'modified_proposed_date',
        'rescheduled_date',
        'reschedule_reason',
        'cancellation_reason',
        'relations_approval_on_reschedule',
        'actual_date',
        'is_in_agenda',
        'is_from_agenda',
        'responsible_party',
        'agenda_event_id',
        'description',
        'has_sponsor',
        'has_partners',
        'has_official_attendance',
        'official_attendance_details',
        'needs_official_letters',
        'needs_official_correspondence',
        'correspondence_reason_id',
        'correspondence_status',
        'official_correspondence_reason',
        'official_correspondence_target',
        'official_correspondence_brief',
        'letter_purpose',
        'location_type',
        'location_details',

        'internal_location',
        'building',
        'room',
        'outside_place_name',
        'outside_google_maps_url',
        'outside_contact_number',
        'external_liaison_name',
        'external_liaison_phone',
        'outside_address',
        'time_from',
        'time_to',
        'execution_time',
        'target_group',
        'target_group_id',
        'event_type_id',
        'target_group_other',
        'short_description',
        'work_teams_count',
        'volunteer_need',
        'needs_volunteers',
        'volunteers_required',
        'volunteers_count',
        'required_volunteers',
        'volunteer_age_range',
        'volunteer_gender',
        'volunteer_tasks_summary',
        'expected_attendance',
        'expected_attendance_from',
        'expected_attendance_to',
        'actual_attendance',
        'attendance_rate',
        'attendance_gap',
        'attendance_percentage',
        'attendance_notes',
        'audience_satisfaction_percent',
        'evaluation_score',
        'evaluation_reason',
        'evaluation_assigned_user_id',
        'evaluation_assigned_at',
        'media_coverage',
        'needs_media_coverage',
        'media_coverage_notes',
        'requires_programs',
        'is_program_related',
        'requires_workshops',
        'requires_communications',
        'execution_needs_payload',
        'execution_needs_followup',
        'post_execution_payload',
        'status',
        'execution_status',
        'plan_stage',
        'plan_version',
        'previous_version_id',
        'lifecycle_status',
        'participation_status',
        'plan_type',
        'branch_plan_file',
        'planning_attachment',
        'is_archived',
        'archived_year',
        'relations_officer_approval_status',
        'relations_manager_approval_status',
        'programs_officer_approval_status',
        'programs_manager_approval_status',
        'liaison_approval_status',
        'hq_relations_manager_approval_status',
        'executive_approval_status',
        'executive_review_required',
        'lock_at',
        'is_official',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'is_in_agenda' => 'boolean',
        'is_from_agenda' => 'boolean',
        'has_official_attendance' => 'boolean',
        'needs_official_letters' => 'boolean',
        'needs_official_correspondence' => 'boolean',
        'relations_approval_on_reschedule' => 'boolean',
        'has_sponsor' => 'boolean',
        'has_partners' => 'boolean',
        'needs_volunteers' => 'boolean',
        'volunteers_required' => 'boolean',
        'needs_media_coverage' => 'boolean',
        'requires_programs' => 'boolean',
        'is_program_related' => 'boolean',
        'requires_workshops' => 'boolean',
        'requires_communications' => 'boolean',
        'executive_review_required' => 'boolean',
        'execution_needs_payload' => 'array',
        'execution_needs_followup' => 'array',
        'post_execution_payload' => 'array',
        'proposed_date' => 'date',
        'activity_date' => 'date',
        'modified_proposed_date' => 'date',
        'rescheduled_date' => 'date',
        'actual_date' => 'date',
        'evaluation_assigned_at' => 'datetime',
        'lock_at' => 'datetime',
        'is_official' => 'boolean',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
        'is_archived' => 'boolean',
        'plan_stage' => 'integer',
        'plan_version' => 'integer',
    ];

    public function getPlanningAttachmentAttribute(): ?string
    {
        return $this->attributes['branch_plan_file'] ?? null;
    }

    public function getMonthlyCreatedByBranchRelationsAttribute(): bool
    {
        $creator = $this->creator()->first();

        if ($creator?->hasRole('supervisor')) {
            return true;
        }

        if (! $creator?->hasRole('relations_officer')) {
            return false;
        }

        return ! (bool) optional($creator->branch)->is_main;
    }

    public function getMonthlyCreatedByPrimaryRelationsAttribute(): bool
    {
        return (bool) $this->creator()->first()?->hasRole('relations_officer');
    }

    public function getMonthlyBranchCoordinatorRequiredAttribute(): bool
    {
        if (! $this->monthly_created_by_branch_relations || empty($this->branch_id)) {
            return false;
        }

        return User::query()
            ->role('branch_coordinator')
            ->where(function ($query): void {
                $query->whereHas('assignedBranches', function ($assignedQuery): void {
                    $assignedQuery->where('branches.id', $this->branch_id);
                })->orWhere(function ($fallbackQuery): void {
                    $fallbackQuery
                        ->whereDoesntHave('assignedBranches')
                        ->where('branch_id', $this->branch_id);
                });
            })
            ->exists();
    }

    public function setPlanningAttachmentAttribute($value): void
    {
        $this->attributes['branch_plan_file'] = $value;
    }

    public static function executionNeedDefinitions(): array
    {
        $definitions = (array) config('execution_needs.definitions', []);
        $matrix = self::executionNeedsDecisionMatrix();

        return collect($definitions)
            ->map(function (array $definition, string $key) use ($matrix): array {
                $roles = (array) data_get($matrix, $key.'.roles', []);
                $defaultRoles = (array) ($definition['default_roles'] ?? []);
                $roles = $roles === [] ? $defaultRoles : $roles;

                if ($roles !== []) {
                    $definition['owner_role'] = $roles[0];
                    $definition['decision_roles'] = $roles;
                } else {
                    $definition['owner_role'] = null;
                    $definition['decision_roles'] = [];
                }

                return $definition;
            })
            ->all();
    }

    public static function executionNeedsDecisionMatrix(): array
    {
        return config('execution_needs.decision_matrix', []);
    }


    public function executionNeedsMap(): array
    {
        $payload = $this->execution_needs_payload ?? [];

        return [
            'volunteers' => (bool) $this->needs_volunteers,
            'official_correspondence' => (bool) $this->needs_official_correspondence,
            'media_coverage' => (bool) $this->needs_media_coverage,
            'supplies' => $this->relationLoaded('supplies') ? $this->supplies->isNotEmpty() : $this->supplies()->exists(),
            'official_sponsorship' => (bool) $this->has_sponsor,
            'external_partners' => (bool) $this->has_partners,
            'ceremony_agenda' => (bool) data_get($payload, 'needs_ceremony_agenda', false),
            'transport' => (bool) data_get($payload, 'needs_transport', false),
            'maintenance_workers' => (bool) data_get($payload, 'needs_maintenance_workers', false),
            'gifts_shields' => (bool) data_get($payload, 'needs_gifts', false),
            'programs_participation' => (bool) data_get($payload, 'needs_programs_participation', false),
            'certificates_thanks' => (bool) data_get($payload, 'needs_certificates_and_thanks', false),
            'invitations' => (bool) data_get($payload, 'needs_invitations', false),
        ];
    }

    public function enabledExecutionNeeds(): array
    {
        $enabled = $this->executionNeedsMap();

        return collect(self::executionNeedDefinitions())
            ->filter(fn (array $definition, string $key) => (bool) ($enabled[$key] ?? false))
            ->all();
    }


    protected static function booted(): void
    {
        static::saving(function (self $activity) {
            if ($activity->expected_attendance_from !== null || $activity->expected_attendance_to !== null) {
                $activity->expected_attendance = $activity->expected_attendance_to ?? $activity->expected_attendance_from;
            }

            $expected = (int) ($activity->expected_attendance_to ?? $activity->expected_attendance ?? 0);
            $actual = (int) ($activity->actual_attendance ?? 0);

            if ($expected > 0) {
                $activity->attendance_rate = round($actual / $expected, 4);
                $activity->attendance_percentage = round(($actual / $expected) * 100, 2);
                $activity->attendance_gap = $expected - $actual;
            } else {
                $activity->attendance_rate = null;
                $activity->attendance_percentage = null;
                $activity->attendance_gap = null;
            }
        });

        static::saved(function (self $activity) {
            if ($activity->requires_workshops) {
                WorkshopsRequest::firstOrCreate(['event_id' => $activity->id], ['status' => 'pending']);
            }

            if ($activity->requires_communications || $activity->needs_media_coverage) {
                CommunicationsRequest::firstOrCreate(['event_id' => $activity->id], ['status' => 'pending']);
            }
        });
    }

    public function getExpectedAttendanceRangeLabelAttribute(): string
    {
        $from = $this->expected_attendance_from;
        $to = $this->expected_attendance_to;

        if ($from !== null && $to !== null && (int) $from !== (int) $to) {
            return $from . ' - ' . $to;
        }

        return (string) ($from ?? $to ?? $this->expected_attendance ?? '-');
    }


    public function evaluationAssignee()
    {
        return $this->belongsTo(User::class, 'evaluation_assigned_user_id');
    }

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeEnterpriseFilter($query, array $filters)
    {
        return $query
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->whereYear('proposed_date', $year))
            ->when($filters['month'] ?? null, fn ($q, $month) => $q->where('month', $month))
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['department_id'] ?? null, function ($q, $departmentId) {
                $q->whereHas('agendaEvent', fn ($agenda) => $agenda->where('department_id', $departmentId));
            })
            ->when($filters['event_category_id'] ?? null, function ($q, $categoryId) {
                $q->whereHas('agendaEvent', fn ($agenda) => $agenda->where('event_category_id', $categoryId));
            })
            ->when($filters['plan_type'] ?? null, function ($q, $planType) {
                $q->whereHas('agendaEvent', fn ($agenda) => $agenda->where('plan_type', $planType));
            })
            ->when($filters['event_type'] ?? null, function ($q, $eventType) {
                $q->whereHas('agendaEvent', fn ($agenda) => $agenda->where('event_type', $eventType));
            })
            ->when(array_key_exists('archived', $filters), function ($q) use ($filters) {
                $archived = filter_var($filters['archived'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($archived !== null) {
                    $q->where('is_archived', $archived);
                }
            });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function previousVersion()
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function newerVersions()
    {
        return $this->hasMany(self::class, 'previous_version_id');
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

    public function officialCorrespondenceAttachments()
    {
        return $this->attachments()->where('file_type', 'official_correspondence');
    }

    public function approvals()
    {
        return $this->hasMany(MonthlyActivityApproval::class);
    }

    public function notes()
    {
        return $this->hasMany(ActivityNote::class, 'activity_id')->latest();
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
    public function executionNeedVolunteers()
    {
        return $this->hasOne(MonthlyActivityNeedVolunteers::class, 'monthly_activity_id');
    }

    public function executionNeedOfficialCorrespondence()
    {
        return $this->hasOne(MonthlyActivityNeedOfficialCorrespondence::class, 'monthly_activity_id');
    }

    public function executionNeedMediaCoverage()
    {
        return $this->hasOne(MonthlyActivityNeedMediaCoverage::class, 'monthly_activity_id');
    }

    public function executionNeedSupplies()
    {
        return $this->hasOne(MonthlyActivityNeedSupplies::class, 'monthly_activity_id');
    }

    public function executionNeedOfficialSponsorship()
    {
        return $this->hasOne(MonthlyActivityNeedOfficialSponsorship::class, 'monthly_activity_id');
    }

    public function executionNeedExternalPartners()
    {
        return $this->hasOne(MonthlyActivityNeedExternalPartners::class, 'monthly_activity_id');
    }

    public function executionNeedCeremonyAgenda()
    {
        return $this->hasOne(MonthlyActivityNeedCeremonyAgenda::class, 'monthly_activity_id');
    }

    public function executionNeedTransport()
    {
        return $this->hasOne(MonthlyActivityNeedTransport::class, 'monthly_activity_id');
    }

    public function executionNeedMaintenanceWorkers()
    {
        return $this->hasOne(MonthlyActivityNeedMaintenanceWorkers::class, 'monthly_activity_id');
    }

    public function executionNeedGiftsShields()
    {
        return $this->hasOne(MonthlyActivityNeedGiftsShields::class, 'monthly_activity_id');
    }

    public function executionNeedProgramsParticipation()
    {
        return $this->hasOne(MonthlyActivityNeedProgramsParticipation::class, 'monthly_activity_id');
    }

    public function executionNeedCertificatesThanks()
    {
        return $this->hasOne(MonthlyActivityNeedCertificatesThanks::class, 'monthly_activity_id');
    }

    public function executionNeedInvitations()
    {
        return $this->hasOne(MonthlyActivityNeedInvitations::class, 'monthly_activity_id');
    }

    public function attendance()
    {
        return $this->hasOne(ActivityAttendance::class);
    }

    public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }

    public function targetGroups()
    {
        return $this->belongsToMany(TargetGroup::class, 'event_target_group')
            ->withPivot('custom_text')
            ->withTimestamps();
    }

    public function evaluationResponses()
    {
        return $this->hasMany(MonthlyActivityEvaluationResponse::class);
    }

    public function followups()
    {
        return $this->hasMany(MonthlyActivityFollowup::class);
    }

    public function donations()
    {
        return $this->hasMany(DonationCash::class);
    }

    public function workshopsRequest()
    {
        return $this->hasOne(WorkshopsRequest::class, 'event_id');
    }

    public function communicationsRequest()
    {
        return $this->hasOne(CommunicationsRequest::class, 'event_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }


    public function workflowInstance()
    {
        return $this->morphOne(WorkflowInstance::class, 'entity');
    }

}
