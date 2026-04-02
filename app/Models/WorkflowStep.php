<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'step_key',
        'name_ar',
        'name_en',
        'step_type',
        'approval_level',
        'role_id',
        'permission_id',
        'is_editable',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Permission::class, 'permission_id');
    }
}
