<?php

namespace App\Modules\Events\Models;

use App\Models\TargetGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTargetGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'target_group_id',
        'target_group_custom_text',
        'beneficiary_segment_id',
        'segment_custom_text',
        'planned_count',
        'actual_count',
        'notes',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'target_group_id' => 'integer',
        'beneficiary_segment_id' => 'integer',
        'planned_count' => 'integer',
        'actual_count' => 'integer',
    ];

    public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }

    public function beneficiarySegment()
    {
        return $this->belongsTo(BeneficiarySegment::class);
    }
}
