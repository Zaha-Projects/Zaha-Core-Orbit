<?php

namespace App\Modules\Events\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RamadanPeriod extends Model
{
    protected $fillable = ['year', 'hijri_year', 'start_date', 'end_date', 'suggested_start_date', 'suggested_end_date', 'calculation_source', 'synced_at', 'is_confirmed', 'is_active'];

    protected $casts = [
        'year' => 'integer', 'hijri_year' => 'integer',
        'start_date' => 'immutable_date', 'end_date' => 'immutable_date',
        'suggested_start_date' => 'immutable_date', 'suggested_end_date' => 'immutable_date', 'synced_at' => 'datetime',
        'is_confirmed' => 'boolean', 'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $period): void {
            if ($period->is_active && ! $period->is_confirmed) {
                throw new \LogicException('A Ramadan period must be confirmed before activation.');
            }
        });
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

    public function useSuggestedDates(): void
    {
        if (! $this->suggested_start_date || ! $this->suggested_end_date) throw new \LogicException('No suggested Ramadan dates are available.');
        $this->forceFill(['start_date' => $this->suggested_start_date, 'end_date' => $this->suggested_end_date, 'is_confirmed' => false])->save();
    }

    public function confirm(): void
    {
        if (! $this->hijri_year || ! $this->start_date || ! $this->end_date || $this->start_date->gt($this->end_date)) throw new \LogicException('Valid operational dates and Hijri year are required.');
        $this->forceFill(['is_confirmed' => true])->save();
    }

    public function activate(): void
    {
        if (! $this->is_confirmed) throw new \LogicException('Confirm the Ramadan period before activation.');
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
