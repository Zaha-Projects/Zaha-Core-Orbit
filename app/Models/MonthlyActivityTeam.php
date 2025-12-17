<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'user_id',
        'member_name',
        'role_desc',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
