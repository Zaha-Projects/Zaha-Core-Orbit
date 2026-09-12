<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectVolunteerRequirement extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'subject_type', 'subject_id', 'beneficiary_segment_id', 'gender',
        'planned_count', 'actual_count', 'tasks_summary', 'status',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'planned_count' => 'integer',
        'actual_count' => 'integer',
    ];

    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    public function beneficiarySegment()
    {
        return $this->belongsTo(BeneficiarySegment::class);
    }
}
