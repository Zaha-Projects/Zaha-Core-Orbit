<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'module',
        'active_module',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    protected static function booted(): void
    {
        static::saving(function (self $workflow): void {
            $workflow->active_module = $workflow->is_active ? $workflow->module : null;
        });
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('step_order')->orderBy('approval_level');
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }
}
