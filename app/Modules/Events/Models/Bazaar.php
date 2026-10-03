<?php

namespace App\Modules\Events\Models;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Bazaar extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_POST_EXECUTION = 'post_execution';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['branch_id', 'relations_officer_id', 'name', 'bazaar_date', 'starts_at', 'ends_at', 'location_type', 'location_name', 'location_details', 'map_url', 'planned_table_count', 'actual_occupied_table_count', 'status', 'approved_by', 'submitted_at', 'approved_at', 'execution_started_at', 'post_execution_submitted_at', 'verified_by', 'verified_at', 'verification_note'];
    protected $casts = ['bazaar_date' => 'date', 'planned_table_count' => 'integer', 'actual_occupied_table_count' => 'integer', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'execution_started_at' => 'datetime', 'post_execution_submitted_at' => 'datetime', 'verified_at' => 'datetime'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function relationsOfficer() { return $this->belongsTo(User::class, 'relations_officer_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function tables() { return $this->hasMany(BazaarTable::class)->orderBy('table_number'); }
    public function targetGroupSelections() { return $this->hasMany(SubjectTargetGroup::class, 'subject_id')->where('subject_type', EventSubjectTypes::BAZAAR); }
    public function executionNeeds() { return $this->hasMany(SubjectExecutionNeed::class, 'subject_id')->where('subject_type', EventSubjectTypes::BAZAAR); }
    public function isPlanningEditable(): bool { return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true); }
}
