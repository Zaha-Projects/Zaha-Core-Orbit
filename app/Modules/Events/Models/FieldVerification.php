<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldVerification extends Model
{
    use HasFactory;

    public const MATCHED = 'matched';
    public const MISMATCHED = 'mismatched';
    public const NOT_OBSERVED = 'not_observed';
    public const NOT_APPLICABLE = 'not_applicable';

    protected $fillable = [
        'monitoring_report_id', 'detail_type', 'detail_id', 'field_key',
        'field_label', 'planned_value', 'actual_value', 'match_status',
        'note', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'detail_id' => 'integer',
        'planned_value' => 'array',
        'actual_value' => 'array',
        'verified_at' => 'datetime',
    ];

    public static function matchStatuses(): array
    {
        return [self::MATCHED, self::MISMATCHED, self::NOT_OBSERVED, self::NOT_APPLICABLE];
    }

    public function monitoringReport()
    {
        return $this->belongsTo(MonitoringReport::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
