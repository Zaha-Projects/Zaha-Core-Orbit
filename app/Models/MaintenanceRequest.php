<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'logged_at',
        'type',
        'category',
        'description',
        'priority',
        'status',
        'branch_head_status',
        'branch_head_note',
        'branch_head_updated_at',
        'maintenance_track_status',
        'maintenance_track_note',
        'maintenance_track_updated_at',
        'it_track_status',
        'it_track_note',
        'it_track_updated_at',
        'support_resources',
        'support_party',
        'root_cause_branch',
        'root_cause_maintenance',
        'root_cause_it',
        'closure_summary',
        'branch_id',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'branch_head_updated_at' => 'datetime',
        'maintenance_track_updated_at' => 'datetime',
        'it_track_updated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workDetails()
    {
        return $this->hasMany(MaintenanceWorkDetail::class);
    }

    public function approvals()
    {
        return $this->hasMany(MaintenanceApproval::class);
    }

    public function attachments()
    {
        return $this->hasMany(MaintenanceAttachment::class);
    }
}
