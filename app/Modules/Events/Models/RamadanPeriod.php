<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Model;

class RamadanPeriod extends Model
{
    protected $fillable = ['year', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'year' => 'integer', 'start_date' => 'immutable_date',
        'end_date' => 'immutable_date', 'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereColumn('start_date', '<=', 'end_date');
    }

    public static function rules(string $prefix = ''): array
    {
        return [
            $prefix.'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            $prefix.'start_date' => ['required', 'date_format:Y-m-d', new \App\Modules\Events\Support\RamadanPeriodYear($prefix.'year')],
            $prefix.'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$prefix.'start_date', new \App\Modules\Events\Support\RamadanPeriodYear($prefix.'year')],
            $prefix.'is_active' => ['nullable', 'boolean'],
        ];
    }
}
