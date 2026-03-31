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
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            if (! empty($this->name_ar)) {
                return $this->name_ar;
            }

            $localized = __('app.acl.roles.' . $this->name);
            if ($localized !== 'app.acl.roles.' . $this->name) {
                return $localized;
            }

            return $this->name_en ?: $this->name;
        }

        if (! empty($this->name_en)) {
            return $this->name_en;
        }

        $localized = __('app.acl.roles.' . $this->name);
        if ($localized !== 'app.acl.roles.' . $this->name) {
            return $localized;
        }

        return $this->name_ar ?: $this->name;
    }
}
