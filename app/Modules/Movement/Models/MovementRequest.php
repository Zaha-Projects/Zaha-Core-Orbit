<?php

namespace App\Modules\Movement\Models;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movement request header (plan §7.6). `status` is the decision lifecycle only (§8.4);
 * operational progress lives in `fulfilment_status` (§8.5, Phase 4 subset per Revision 5).
 */
class MovementRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_RETURNED, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED];

    /** §8.4, exactly. Checked by MovementRequestWorkflow before every change. */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_SUBMITTED, self::STATUS_CANCELLED],
        self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED, self::STATUS_RETURNED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_UNDER_REVIEW => [self::STATUS_APPROVED, self::STATUS_RETURNED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_RETURNED => [self::STATUS_SUBMITTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_CANCELLED],
        self::STATUS_REJECTED => [],
        self::STATUS_CANCELLED => [],
    ];

    /** Requester edits are allowed only here (§8.4). */
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_RETURNED];

    // Phase 4 subset of §8.5 (Revision 5); the full derivation arrives in Phase 5.
    public const FULFILMENT_NOT_APPLICABLE = 'not_applicable';
    public const FULFILMENT_UNALLOCATED = 'unallocated';
    public const FULFILMENT_CANCELLED = 'cancelled';

    public const DIRECTION_ONE_WAY = 'one_way';
    public const DIRECTION_ROUND_TRIP = 'round_trip';
    public const DIRECTIONS = [self::DIRECTION_ONE_WAY, self::DIRECTION_ROUND_TRIP];

    protected $table = 'movement_requests';

    protected $fillable = [
        'reference_no', 'requester_id', 'branch_id', 'department_id', 'context_type', 'context_id', 'context_label',
        'trip_direction', 'purpose', 'notes', 'status', 'fulfilment_status', 'fulfilment_dropped_at', 'client_token',
        'submitted_at', 'decided_at', 'decided_by', 'decision_comment', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected $casts = [
        'fulfilment_dropped_at' => 'datetime',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function legs() { return $this->hasMany(MovementRequestLeg::class)->orderByRaw("CASE WHEN leg_type = 'outbound' THEN 0 ELSE 1 END"); }
    public function outboundLeg() { return $this->hasOne(MovementRequestLeg::class)->where('leg_type', MovementRequestLeg::TYPE_OUTBOUND); }
    public function returnLeg() { return $this->hasOne(MovementRequestLeg::class)->where('leg_type', MovementRequestLeg::TYPE_RETURN); }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }
}
