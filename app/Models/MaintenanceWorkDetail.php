<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceWorkDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'maintenance_request_id',
        'start_from',
        'end_to',
        'team_desc',
        'resources_type',
        'support_party',
        'estimated_cost',
        'root_cause_analysis',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'start_from' => 'datetime',
        'end_to' => 'datetime',
        'estimated_cost' => 'decimal:2',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
