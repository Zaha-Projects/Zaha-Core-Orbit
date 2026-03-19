<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_event_id',
        'entity_type',
        'entity_id',
        'participation_status',
        'proposed_date',
        'actual_execution_date',
        'branch_plan_file',
        'updated_by',
    ];

    protected $casts = [
        'proposed_date' => 'date',
        'actual_execution_date' => 'date',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function departmentUnit()
    {
        return $this->belongsTo(DepartmentUnit::class, 'entity_id')->where('entity_type', 'department_unit');
    }
}
