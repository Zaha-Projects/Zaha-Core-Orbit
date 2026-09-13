<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionNeedType extends Model
{
    use HasFactory;

    public const MAPPING_EXACT = 'EXACT';
    public const MAPPING_RENAMED_EQUIVALENT = 'RENAMED_EQUIVALENT';
    public const MAPPING_MERGED = 'MERGED';
    public const MAPPING_SPLIT = 'SPLIT';
    public const MAPPING_NO_SAFE_MAPPING = 'NO_SAFE_MAPPING';

    public const CANONICAL_DEFINITIONS = [
        'volunteers' => ['name' => 'الحاجة للمتطوعين', 'ramadan' => true],
        'official_correspondence' => ['name' => 'الحاجة للمخاطبة الرسمية', 'ramadan' => true],
        'media_coverage' => ['name' => 'الحاجة لتغطية إعلامية', 'ramadan' => true],
        'supplies' => ['name' => 'الحاجة للمستلزمات', 'ramadan' => true],
        'official_sponsorship' => ['name' => 'الحاجة لرعاية رسمية', 'ramadan' => false],
        'external_partners' => ['name' => 'الحاجة لشركاء خارجيين', 'ramadan' => false],
        'ceremony_agenda' => ['name' => 'الحاجة لوجود أجندة حفل', 'ramadan' => false],
        'transport' => ['name' => 'الحاجة لتأمين مواصلات', 'ramadan' => true],
        'maintenance_workers' => ['name' => 'الحاجة لعمال صيانة بالموقع', 'ramadan' => true],
        'gifts_shields' => ['name' => 'الحاجة لهدايا ودروع', 'ramadan' => true],
        'programs_participation' => ['name' => 'الحاجة لمشاركة البرامج', 'ramadan' => false],
        'certificates' => ['name' => 'الشهادات', 'ramadan' => false],
        'thanks_letters' => ['name' => 'كتب الشكر', 'ramadan' => false],
        'invitations' => ['name' => 'الحاجة إلى بطاقات دعوة', 'ramadan' => true],
    ];

    public const LEGACY_MAPPINGS = [
        'volunteers' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['volunteers']],
        'official_correspondence' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['official_correspondence']],
        'media_coverage' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['media_coverage']],
        'supplies' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['supplies']],
        'official_sponsorship' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['official_sponsorship']],
        'external_partners' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['external_partners']],
        'ceremony_agenda' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['ceremony_agenda']],
        'ceremony' => ['classification' => self::MAPPING_RENAMED_EQUIVALENT, 'canonical' => ['ceremony_agenda']],
        'transport' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['transport']],
        'maintenance_workers' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['maintenance_workers']],
        'maintenance' => ['classification' => self::MAPPING_RENAMED_EQUIVALENT, 'canonical' => ['maintenance_workers']],
        'gifts_shields' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['gifts_shields']],
        'gifts' => ['classification' => self::MAPPING_RENAMED_EQUIVALENT, 'canonical' => ['gifts_shields']],
        'programs_participation' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['programs_participation']],
        'programs' => ['classification' => self::MAPPING_RENAMED_EQUIVALENT, 'canonical' => ['programs_participation']],
        'certificates' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['certificates']],
        'thanks_letters' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['thanks_letters']],
        'certificates_thanks' => ['classification' => self::MAPPING_SPLIT, 'canonical' => ['certificates', 'thanks_letters']],
        'invitations' => ['classification' => self::MAPPING_EXACT, 'canonical' => ['invitations']],
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
        'is_canonical',
        'is_monthly_activity',
        'is_ramadan_iftar',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_canonical' => 'boolean',
        'is_monthly_activity' => 'boolean',
        'is_ramadan_iftar' => 'boolean',
    ];

    public static function canonicalCodes(): array
    {
        return array_keys(self::CANONICAL_DEFINITIONS);
    }

    public static function mappingForLegacy(string $code): ?array
    {
        return self::LEGACY_MAPPINGS[$code] ?? null;
    }

    public function scopeCanonical($query)
    {
        return $query->where('is_canonical', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRamadanIftars($query)
    {
        return $query->where('is_ramadan_iftar', true);
    }
}
