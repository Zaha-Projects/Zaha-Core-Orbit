<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'entity_type',
        'entity_id',
        'action_type',
        'status',
        'performed_by',
        'notes',
        'meta',
        'performed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'performed_at' => 'datetime',
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
