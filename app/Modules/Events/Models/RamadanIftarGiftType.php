<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanIftarGiftType extends Model
{
    protected $fillable = ['code', 'name_ar', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
