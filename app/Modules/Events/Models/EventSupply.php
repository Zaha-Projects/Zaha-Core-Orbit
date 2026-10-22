<?php

namespace App\Modules\Events\Models;

use App\Modules\Events\Models\MonthlyActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSupply extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    protected $table = 'event_supplies';

    protected $fillable = [
        'monthly_activity_id',
        'subject_type',
        'subject_id',
        'item_name',
        'status',
        'available',
        'provider_type',
        'provider_name',
        'quantity',
        'planned_quantity',
        'planned_available',
        'actual_quantity',
        'is_available',
        'estimated_value',
        'notes',
    ];

    protected $casts = [
        'available' => 'boolean',
        'subject_id' => 'integer',
        'planned_quantity' => 'integer',
        'planned_available' => 'boolean',
        'actual_quantity' => 'integer',
        'is_available' => 'boolean',
        'estimated_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $supply): void {
            if ($supply->subject_type !== null && $supply->status === null) {
                $supply->status = self::STATUS_PENDING;
            }
        });
    }

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }
}
