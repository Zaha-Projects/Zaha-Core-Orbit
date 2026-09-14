<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityTeam extends Model
{
    use HasFactory;

    protected $table = 'monthly_activity_team';

    protected $fillable = [
        'monthly_activity_id',
        'execution_team_id',
        'user_id',
        'team_name',
        'member_name',
        'member_email',
        'role_desc',
        'phone',
        'role_name',
        'task_description',
        'task_completed',
        'actual_task_note',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'task_completed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function executionTeam()
    {
        return $this->belongsTo(\App\Modules\Events\Models\ExecutionTeam::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
