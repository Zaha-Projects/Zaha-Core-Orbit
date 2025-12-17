<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'logged_at',
        'type',
        'category',
        'description',
        'priority',
        'status',
        'branch_id',
        'center_id',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
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
