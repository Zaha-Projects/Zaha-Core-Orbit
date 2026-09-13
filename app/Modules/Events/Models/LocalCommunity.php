<?php

namespace App\Modules\Events\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalCommunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'location_name',
        'address',
        'google_maps_url',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
