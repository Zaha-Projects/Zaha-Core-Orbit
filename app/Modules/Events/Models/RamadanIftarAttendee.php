<?php

namespace App\Modules\Events\Models;

use App\Models\TargetGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamadanIftarAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'ramadan_iftar_id', 'full_name', 'phone', 'age', 'target_group_id',
        'beneficiary_segment_id', 'attended', 'checked_in_at', 'notes',
    ];

    protected $casts = [
        'age' => 'integer',
        'attended' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public function ramadanIftar()
    {
        return $this->belongsTo(RamadanIftar::class);
    }

    public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }

    public function beneficiarySegment()
    {
        return $this->belongsTo(BeneficiarySegment::class);
    }
}
