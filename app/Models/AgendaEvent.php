<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\WorkflowInstance;

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
        'owner_department_id',
        'event_category_id',
        'event_category',
        'plan_type',
        'event_type',
        'is_mandatory',
        'is_unified',
        'status',
        'is_archived',
        'is_active',
        'archived_year',
        'relations_approval_status',
        'executive_approval_status',
        'created_by',
        'approved_by_relations_at',
        'approved_by_executive_at',
        'notes',
        'agenda_plan_file',
        'version',
    ];

    protected $casts = [
        'event_date' => 'date',
        'approved_by_relations_at' => 'datetime',
        'approved_by_executive_at' => 'datetime',
        'is_archived' => 'boolean',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'is_unified' => 'boolean',
        'version' => 'integer',
    ];

    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeEnterpriseFilter($query, array $filters)
    {
        return $query
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->whereYear('event_date', $year))
            ->when($filters['month'] ?? null, fn ($q, $month) => $q->where('month', $month))
            ->when($filters['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($filters['event_category_id'] ?? null, fn ($q, $categoryId) => $q->where('event_category_id', $categoryId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['plan_type'] ?? null, fn ($q, $planType) => $q->where('plan_type', $planType))
            ->when($filters['event_type'] ?? null, fn ($q, $eventType) => $q->where('event_type', $eventType))
            ->when($filters['branch_id'] ?? null, function ($q, $branchId) {
                $q->forBranchAudience([(int) $branchId]);
            })
            ->when(array_key_exists('archived', $filters), function ($q) use ($filters) {
                $archived = filter_var($filters['archived'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($archived !== null) {
                    $q->where('is_archived', $archived);
                }
            });
    }

    public function scopeForBranchAudience($query, array $branchIds, ?int $creatorId = null, ?int $selectedEventId = null)
    {
        $branchIds = collect($branchIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($branchIds === []) {
            return $query;
        }

        return $query->where(function ($audienceQuery) use ($branchIds, $creatorId, $selectedEventId) {
            $audienceQuery
                ->where('event_type', 'optional')
                ->orWhereHas('participations', function ($participationQuery) use ($branchIds) {
                    $participationQuery
                        ->where('entity_type', 'branch')
                        ->whereIn('entity_id', $branchIds)
                        ->where('participation_status', 'participant');
                });

            if ($creatorId) {
                $audienceQuery->orWhere('created_by', $creatorId);
            }

            if ($selectedEventId) {
                $audienceQuery->orWhere($audienceQuery->getModel()->getQualifiedKeyName(), $selectedEventId);
            }
        });
    }

    public function isVisibleToAnyBranch(array $branchIds): bool
    {
        $branchIds = collect($branchIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ((string) $this->event_type === 'optional') {
            return true;
        }

        if ($branchIds === []) {
            return false;
        }

        return $this->participations()
            ->where('entity_type', 'branch')
            ->whereIn('entity_id', $branchIds)
            ->where('participation_status', 'participant')
            ->exists();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'owner_department_id');
    }


    public function partnerDepartments()
    {
        return $this->belongsToMany(Department::class, 'agenda_event_partner_departments')
            ->withTimestamps();
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

    public function workflowInstance()
    {
        return $this->morphOne(WorkflowInstance::class, 'entity');
    }
}
