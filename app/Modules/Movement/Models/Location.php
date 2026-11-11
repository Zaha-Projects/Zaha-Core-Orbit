<?php

namespace App\Modules\Movement\Models;

use App\Models\Branch;
use App\Support\GoogleMaps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Plan §7.5 / §16 screen 2b: saved places. branch_id is metadata only (no branch isolation, R5). */
class Location extends Model
{
    use SoftDeletes;

    protected $table = 'movement_locations';

    protected $fillable = ['name_ar', 'name_en', 'address', 'latitude', 'longitude', 'google_place_id', 'maps_url', 'branch_id', 'is_active'];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float', 'is_active' => 'boolean'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) (app()->getLocale() === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en));
    }

    /**
     * Coordinates from a pasted Google Maps link, via the existing GoogleMaps parser.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function coordinatesFromMapsUrl(?string $url): ?array
    {
        $query = GoogleMaps::locationQuery($url);
        if ($query === null || preg_match('/^\s*(-?\d{1,3}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $query, $m) !== 1) {
            return null;
        }

        [$lat, $lng] = [(float) $m[1], (float) $m[2]];

        return abs($lat) <= 90 && abs($lng) <= 180 ? [$lat, $lng] : null;
    }
}
