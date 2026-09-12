<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiarySegment extends Model
{
    use HasFactory;

    public const DIMENSION_AGE = 'age';
    public const DIMENSION_GENDER = 'gender';
    public const DIMENSION_SOCIAL = 'social';
    public const DIMENSION_OTHER = 'other';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'dimension',
        'minimum_age',
        'maximum_age',
        'is_other',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'minimum_age' => 'integer',
        'maximum_age' => 'integer',
        'is_other' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function dimensions(): array
    {
        return [
            self::DIMENSION_AGE,
            self::DIMENSION_GENDER,
            self::DIMENSION_SOCIAL,
            self::DIMENSION_OTHER,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }
}
