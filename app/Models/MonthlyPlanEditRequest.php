<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPlanEditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id', 'request_type', 'entity_type', 'entity_id', 'branch_id', 'reason', 'status',
        'current_approver_id', 'approval_history', 'old_values', 'new_values', 'changed_values',
        'approved_version_id', 'requested_at', 'decided_at',
    ];

    protected $casts = [
        'approval_history' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_values' => 'array',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function currentApprover() { return $this->belongsTo(User::class, 'current_approver_id'); }
    public function monthlyActivity() { return $this->belongsTo(MonthlyActivity::class, 'entity_id'); }
    public function approvedVersion() { return $this->belongsTo(MonthlyActivity::class, 'approved_version_id'); }
    public function workflowInstance() { return $this->morphOne(WorkflowInstance::class, 'entity'); }
}
