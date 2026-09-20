<?php

namespace App\Models;

use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Support\EventAggregateIdentity;
use Illuminate\Database\Eloquent\Model;

class OfficialCorrespondence extends Model
{
    protected $fillable = [
        'correspondable_type',
        'correspondable_id',
        'reason',
        'target',
        'brief',
    ];

    public function correspondable()
    {
        if (EventAggregateIdentity::legacyFor((string) $this->correspondable_type) === EventAggregateIdentity::MONTHLY_ACTIVITY_LEGACY) {
            return $this->belongsTo(MonthlyActivity::class, 'correspondable_id');
        }

        return $this->morphTo();
    }
}
