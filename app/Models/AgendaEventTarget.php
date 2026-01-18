<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaEventTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_event_id',
        'target_type',
        'target_id',
        'is_participant',
    ];

    protected $casts = [
        'is_participant' => 'boolean',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }
}
