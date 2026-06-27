<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\WorkflowInstance;

class MonthlyActivity extends Model
{
    use HasFactory, SoftDeletes;

    public const EXECUTION_NEED_DEFINITIONS = [
        'volunteers' => [
            'label' => 'الحاجة للمتطوعين',
            'owner_role' => 'volunteer_coordinator',
        ],
        'official_correspondence' => [
            'label' => 'الحاجة للمخاطبة الرسمية',
            'owner_role' => 'branch_coordinator',
        ],
        'media_coverage' => [
            'label' => 'الحاجة لتغطية إعلامية',
            'owner_role' => 'communication_head',
        ],
        'supplies' => [
            'label' => 'الحاجة للمستلزمات',
            'owner_role' => null,
        ],
        'official_sponsorship' => [
            'label' => 'الحاجة لرعاية رسمية',
            'owner_role' => null,
        ],
        'external_partners' => [
            'label' => 'الحاجة لشركاء خارجيين',
            'owner_role' => null,
        ],
        'ceremony_agenda' => [
            'label' => 'الحاجة لوجود أجندة حفل',
            'owner_role' => null,
        ],
        'transport' => [
            'label' => 'الحاجة لتأمين مواصلات',
            'owner_role' => null,
        ],
        'maintenance_workers' => [
            'label' => 'الحاجة لعمال صيانة بالموقع',
            'owner_role' => null,
        ],
        'gifts_shields' => [
            'label' => 'الحاجة لهدايا ودروع',
            'owner_role' => null,
        ],
        'programs_participation' => [
            'label' => 'الحاجة لمشاركة البرامج',
            'owner_role' => null,
        ],
        'certificates_thanks' => [
            'label' => 'الحاجة لشهادات وكتب شكر',
            'owner_role' => null,
        ],
        'invitations' => [
            'label' => 'الحاجة إلى بطاقات دعوة',
            'owner_role' => null,
        ],
    ];

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
        'needs_official_correspondence',
        'correspondence_reason_id',
        'correspondence_status',
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
        'needs_volunteers',
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
        'version_number',
        'previous_version_id',
        'parent_version_id',
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
        'needs_official_correspondence' => 'boolean',
        'relations_approval_on_reschedule' => 'boolean',
        'has_sponsor' => 'boolean',
        'has_partners' => 'boolean',
        'needs_volunteers' => 'boolean',
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
        'version_number' => 'integer',
    ];

    public function executionStatusForDisplay(): string
    {
        $executionStatus = (string) ($this->execution_status ?: 'planned');

        if (in_array($executionStatus, ['cancelled', 'postponed'], true)) {
            return $executionStatus;
        }

        if ($this->hasExecutionEvidenceForDisplay()) {
            return 'executed';
        }

        return 'planned';
    }

    public function hasExecutionEvidenceForDisplay(): bool
    {
        return filled($this->actual_date)
            || filled($this->actual_attendance)
            || filled($this->post_execution_payload)
            || in_array((string) $this->status, ['executed', 'completed', 'closed', 'post_execution_submitted'], true)
            || in_array((string) $this->lifecycle_status, ['Executed', 'Evaluated', 'Closed'], true);
    }

    public function getPlanningAttachmentAttribute(): ?string
    {
        return $this->attributes['branch_plan_file'] ?? null;
    }

    public function getVolunteerNeedAttribute(): ?string
    {
        return $this->getRelationValue('volunteerNeed')?->volunteer_need;
    }

    public function getRequiredVolunteersAttribute(): ?int
    {
        return $this->getRelationValue('volunteerNeed')?->required_volunteers;
    }

    public function getVolunteerAgeRangeAttribute(): ?string
    {
        return $this->getRelationValue('volunteerNeed')?->volunteer_age_range;
    }

    public function getVolunteerGenderAttribute(): ?string
    {
        return $this->getRelationValue('volunteerNeed')?->volunteer_gender;
    }

    public function getVolunteerTasksSummaryAttribute(): ?string
    {
        return $this->getRelationValue('volunteerNeed')?->volunteer_tasks_summary;
    }

    public function getVolunteersRequiredAttribute(): bool
    {
        return (bool) ($this->getRelationValue('volunteerNeed')?->volunteers_required);
    }

    public function getVolunteersCountAttribute(): ?int
    {
        return $this->getRelationValue('volunteerNeed')?->volunteers_count;
    }

    public function getOfficialCorrespondenceReasonAttribute(): ?string
    {
        return $this->getRelationValue('officialCorrespondence')?->reason;
    }

    public function getOfficialCorrespondenceTargetAttribute(): ?string
    {
        return $this->getRelationValue('officialCorrespondence')?->target;
    }

    public function getOfficialCorrespondenceBriefAttribute(): ?string
    {
        return $this->getRelationValue('officialCorrespondence')?->brief;
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
        $matrix = self::executionNeedsDecisionMatrix();

        return collect(self::EXECUTION_NEED_DEFINITIONS)
            ->map(function (array $definition, string $key) use ($matrix): array {
                $roles = (array) data_get($matrix, $key.'.roles', []);
                if ($roles !== []) {
                    $definition['owner_role'] = $roles[0];
                    $definition['decision_roles'] = $roles;
                }

                return $definition;
            })
            ->all();
    }

    public static function executionNeedsDecisionMatrix(): array
    {
        return config('execution_needs.decision_matrix', []);
    }

    public function enabledExecutionNeeds(): array
    {
        $payload = $this->execution_needs_payload ?? [];

        $enabled = [
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
                $request = CommunicationsRequest::firstOrCreate(['event_id' => $activity->id], ['status' => 'pending']);

                if ($request->wasRecentlyCreated) {
                    $recipients = User::query()->where('status', 'active')->role('communication_head')->get();
                    if ($recipients->isNotEmpty()) {
                        app(\App\Services\NotificationService::class)->notifyUsers(
                            $recipients,
                            'communications_request_created',
                            'احتياج تنفيذ لقسم الاتصال',
                            'النشاط "'.$activity->title.'" بحاجة إلى: '.($activity->needs_media_coverage ? 'الحاجة لتغطية إعلامية.' : 'خدمة من قسم الاتصال.'),
                            route('role.programs.communications_requests.index'),
                            ['monthly_activity_id' => $activity->id, 'communications_request_id' => $request->id]
                        );
                    }
                }
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

    public function parentVersion()
    {
        return $this->belongsTo(self::class, 'parent_version_id');
    }

    public function childVersions()
    {
        return $this->hasMany(self::class, 'parent_version_id');
    }

    public function deleteRequests()
    {
        return $this->hasMany(MonthlyPlanDeleteRequest::class, 'entity_id');
    }

    public function editRequests()
    {
        return $this->hasMany(MonthlyPlanEditRequest::class, 'entity_id');
    }


    public function volunteerNeed()
    {
        return $this->hasOne(MonthlyActivityVolunteerNeed::class);
    }

    public function officialCorrespondence()
    {
        return $this->morphOne(OfficialCorrespondence::class, 'correspondable');
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
