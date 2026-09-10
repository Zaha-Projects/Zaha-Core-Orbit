<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectSupply extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'subject_type', 'subject_id', 'item_name', 'planned_quantity',
        'actual_quantity', 'is_available', 'provider_type', 'provider_name',
        'estimated_value', 'status', 'notes',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'planned_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'is_available' => 'boolean',
        'estimated_value' => 'decimal:2',
    ];

    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }
}
