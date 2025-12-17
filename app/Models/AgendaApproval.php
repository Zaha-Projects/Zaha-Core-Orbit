<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_event_id',
        'step',
        'decision',
        'comment',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
