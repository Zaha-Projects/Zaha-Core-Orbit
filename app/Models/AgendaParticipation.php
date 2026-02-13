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
        'updated_by',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

