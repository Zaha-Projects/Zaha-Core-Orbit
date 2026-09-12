<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionTeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_team_id', 'user_id', 'member_name', 'phone', 'role_name',
        'task_description', 'task_completed', 'actual_task_note',
        'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'task_completed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function executionTeam()
    {
        return $this->belongsTo(ExecutionTeam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
