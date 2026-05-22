<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OfficialCorrespondence extends Model
{
    protected $fillable = [
        'correspondable_type',
        'correspondable_id',
        'reason',
        'target',
        'brief',
    ];

    public function correspondable(): MorphTo
    {
        return $this->morphTo();
    }
}

