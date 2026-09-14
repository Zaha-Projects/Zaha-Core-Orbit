<?php

namespace App\Modules\Events\Models;

use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'changed_by',
        'field_name',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
