<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPlanDeleteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id', 'request_type', 'entity_type', 'entity_id', 'branch_id', 'reason', 'status',
        'current_approver_id', 'approval_history', 'requested_at', 'decided_at',
    ];

    protected $casts = [
        'approval_history' => 'array',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function currentApprover() { return $this->belongsTo(User::class, 'current_approver_id'); }
    public function monthlyActivity() { return $this->belongsTo(MonthlyActivity::class, 'entity_id'); }
    public function workflowInstance() { return $this->morphOne(WorkflowInstance::class, 'entity'); }
}
