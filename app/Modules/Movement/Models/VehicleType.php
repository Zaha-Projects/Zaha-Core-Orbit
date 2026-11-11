<?php

namespace App\Modules\Movement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Plan §7.1: manager-maintained vehicle types. */
class VehicleType extends Model
{
    use SoftDeletes;

    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';
    public const SIZE_CLASSES = [self::SIZE_SMALL, self::SIZE_MEDIUM, self::SIZE_LARGE];

    protected $table = 'movement_vehicle_types';

    protected $fillable = ['code', 'name_ar', 'name_en', 'size_class', 'default_capacity', 'is_active', 'sort_order'];

    protected $casts = ['default_capacity' => 'integer', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vehicle_type_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en);
    }
}
