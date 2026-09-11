<?php

namespace App\Modules\Events\Models;

use App\Models\ExecutionNeedType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectExecutionNeed extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'execution_need_type_id',
        'is_required',
        'planned_details',
        'status',
        'actual_details',
        'completed_at',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'execution_need_type_id' => 'integer',
        'is_required' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function scopeForSubject($query, string $subjectType, int $subjectId)
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    public function executionNeedType()
    {
        return $this->belongsTo(ExecutionNeedType::class);
    }
}
