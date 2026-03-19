<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'role',
        'note',
        'coverage_status',
    ];

    public function activity()
    {
        return $this->belongsTo(MonthlyActivity::class, 'activity_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
