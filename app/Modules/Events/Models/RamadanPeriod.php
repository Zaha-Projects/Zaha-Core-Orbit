<?php

namespace App\Modules\Events\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RamadanPeriod extends Model
{
    protected $fillable = ['year', 'hijri_year', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'year' => 'integer', 'hijri_year' => 'integer',
        'start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $period): void {
            if ($period->is_active) {
                static::query()->whereKeyNot($period->getKey())->where('is_active', true)->update(['is_active' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereColumn('start_date', '<=', 'end_date');
    }

    public static function current(): ?self
    {
        $periods = static::query()->active()->orderByDesc('year')->orderByDesc('id')->limit(2)->get();
        if ($periods->count() > 1) {
            throw new \LogicException('More than one Ramadan period is active.');
        }
        return $periods->first();
    }

    public static function contains($date): bool
    {
        try { $date = CarbonImmutable::parse($date)->toDateString(); }
        catch (\Throwable $exception) { return false; }

        return static::query()->active()->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists();
    }

    public function activate(): void
    {
        DB::transaction(function (): void {
            static::query()->orderBy('id')->lockForUpdate()->get();
            static::query()->where('is_active', true)->whereKeyNot($this->getKey())->update(['is_active' => false]);
            $this->forceFill(['is_active' => true])->save();
        }, 5);
    }

    public function iftars()
    {
        return $this->hasMany(RamadanIftar::class);
    }

    public static function rules(string $prefix = '', ?int $ignoreId = null): array
    {
        $unique = \Illuminate\Validation\Rule::unique('ramadan_periods', 'year')->ignore($ignoreId);
        return [
            $prefix.'year' => ['required', 'integer', 'min:2020', 'max:2100', $unique],
            $prefix.'hijri_year' => ['required', 'integer', 'min:1400', 'max:1600'],
            $prefix.'start_date' => ['required', 'date_format:Y-m-d', new \App\Modules\Events\Support\RamadanPeriodYear($prefix.'year')],
            $prefix.'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$prefix.'start_date', new \App\Modules\Events\Support\RamadanPeriodYear($prefix.'year')],
        ];
    }
}
