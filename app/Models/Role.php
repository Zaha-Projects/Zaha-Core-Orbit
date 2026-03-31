<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'guard_name',
    ];

    protected $appends = [
        'display_name',
    ];

    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar') {
            return $this->name_ar ?: ($this->name_en ?: $this->name);
        }

        return $this->name_en ?: ($this->name_ar ?: $this->name);
    }
}
