<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyActivityExternalLocation extends Model
{
    use HasFactory;

    protected $table = 'monthly_activity_external_locations';

    protected $fillable = [
        'monthly_activity_id',
        'outside_contact_number',
        'external_liaison_name',
        'external_liaison_phone',
    ];

    public function monthlyActivity(): BelongsTo
    {
        return $this->belongsTo(MonthlyActivity::class);
    }
}
