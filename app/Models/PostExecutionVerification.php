<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostExecutionVerification extends Model
{
    use HasFactory;

    public const MATCHED = 'matched';
    public const MISMATCHED = 'mismatched';
    public const NOT_OBSERVED = 'not_observed';
    public const NOT_APPLICABLE = 'not_applicable';

    protected $fillable = ['monthly_activity_id', 'branch_id', 'monitoring_report_id', 'detail_type', 'detail_id', 'field_key', 'field_label', 'value_type', 'original_value', 'corrected_value', 'status', 'planned_value', 'actual_value', 'match_status', 'note', 'verified_by', 'verified_at'];
    protected $casts = ['original_value' => 'array', 'corrected_value' => 'array', 'planned_value' => 'array', 'actual_value' => 'array', 'detail_id' => 'integer', 'verified_at' => 'datetime'];

    public static function matchStatuses(): array
    {
        return [self::MATCHED, self::MISMATCHED, self::NOT_OBSERVED, self::NOT_APPLICABLE];
    }

    public function activity() { return $this->belongsTo(MonthlyActivity::class, 'monthly_activity_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function monitoringReport() { return $this->belongsTo(\App\Modules\Events\Models\MonitoringReport::class); }
}
