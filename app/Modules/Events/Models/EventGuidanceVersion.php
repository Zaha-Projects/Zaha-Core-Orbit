<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class EventGuidanceVersion extends Model
{
    use HasFactory;

    public const RAMADAN_IFTAR = 'ramadan_iftar';

    protected $fillable = [
        'code',
        'version_number',
        'title',
        'content',
        'is_active',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'created_by' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $meaningfulFields = ['code', 'version_number', 'title', 'content', 'published_at', 'created_by'];

            if ($version->isDirty($meaningfulFields) && $version->ramadanIftars()->exists()) {
                throw new LogicException('An accepted guidance version cannot be changed. Create a new version instead.');
            }
        });

        static::deleting(function (self $version): void {
            if ($version->ramadanIftars()->exists()) {
                throw new LogicException('An accepted guidance version cannot be deleted.');
            }
        });
    }

    public static function currentForRamadan(): ?self
    {
        $versions = static::query()
            ->where('code', self::RAMADAN_IFTAR)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('version_number')
            ->limit(2)
            ->get();

        if ($versions->count() > 1) {
            throw new LogicException('More than one active Ramadan guidance version is published.');
        }

        return $versions->first();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ramadanIftars()
    {
        return $this->hasMany(RamadanIftar::class, 'guidance_version_id');
    }
}
