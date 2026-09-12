<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringReport extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'subject_type', 'subject_id', 'monitoring_method_id', 'monitor_user_id',
        'observed_at', 'general_notes', 'submitted_at', 'status',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'observed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_RETURNED, self::STATUS_APPROVED];
    }

    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    public function monitoringMethod()
    {
        return $this->belongsTo(MonitoringMethod::class);
    }

    public function monitor()
    {
        return $this->belongsTo(User::class, 'monitor_user_id');
    }

    public function verifications()
    {
        return $this->hasMany(FieldVerification::class);
    }

    public function ramadanIftar()
    {
        return $this->belongsTo(RamadanIftar::class, 'subject_id');
    }
}
