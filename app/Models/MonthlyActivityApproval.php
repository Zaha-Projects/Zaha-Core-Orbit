<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyActivityApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_activity_id',
        'step',
        'decision',
        'comment',
        'approved_by',
        'approved_at',
        'is_edit_request_implemented',
        'implemented_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'implemented_at' => 'datetime',
        'is_edit_request_implemented' => 'boolean',
    ];

    public function monthlyActivity()
    {
        return $this->belongsTo(MonthlyActivity::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
