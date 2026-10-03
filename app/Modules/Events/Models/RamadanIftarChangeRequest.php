<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarChangeRequest extends Model
{
    public const WORKFLOW_MODULE = 'ramadan_iftar_change_requests';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['ramadan_iftar_id', 'requested_by', 'branch_id', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_comment', 'created_version_id'];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function source() { return $this->belongsTo(RamadanIftar::class, 'ramadan_iftar_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function createdVersion() { return $this->belongsTo(RamadanIftar::class, 'created_version_id'); }
    public function workflowInstance()
    {
        return $this->hasOne(WorkflowInstance::class, 'entity_id')
            ->where('entity_type', self::class)
            ->whereHas('workflow', fn ($query) => $query->where('module', self::WORKFLOW_MODULE)->where('is_active', true));
    }
}
