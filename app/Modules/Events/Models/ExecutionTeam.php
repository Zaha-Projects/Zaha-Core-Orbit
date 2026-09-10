<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type', 'subject_id', 'name', 'leader_user_id',
        'planned_members_count', 'actual_members_count', 'notes',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'planned_members_count' => 'integer',
        'actual_members_count' => 'integer',
    ];

    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function members()
    {
        return $this->hasMany(ExecutionTeamMember::class);
    }
}
