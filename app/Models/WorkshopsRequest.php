<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopsRequest extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'status', 'notes', 'assigned_to'];

    public function event()
    {
        return $this->belongsTo(MonthlyActivity::class, 'event_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
