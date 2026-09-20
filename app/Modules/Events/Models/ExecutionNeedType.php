<?php

namespace App\Modules\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutionNeedType extends Model
{
    use HasFactory;

    public const SCOPE_MONTHLY_PLANS = 'monthly_plans';
    public const SCOPE_IFTARS = 'iftars';
    public const SCOPE_BOTH = 'both';
    public const SCOPE_NONE = 'none';

    public const IFTAR_DETAIL_FIELDS = [
        'gifts_shields' => 'gifts', 'supplies' => 'supplies',
        'execution_team' => 'execution_teams', 'volunteers' => 'volunteer_requirements',
    ];

    public static function ramadanAvailableTypes()
    {
        return static::query()->canonical()->availableFor(EventSubjectTypes::RAMADAN_IFTAR)->orderBy('sort_order')->get();
    }

    public function scopeAvailableFor($query, string $subjectType)
    {
        EventSubjectTypes::modelFor($subjectType);

        return $query->active()->where($subjectType === EventSubjectTypes::MONTHLY_ACTIVITY ? 'is_monthly_activity' : 'is_ramadan_iftar', true);
    }

    public function scopeCustom($query)
    {
        return $query->whereNotIn('code', array_keys(self::MONTHLY_INPUT_FIELDS));
    }

    public const MONTHLY_INPUT_FIELDS = [
        'execution_team' => ['team_groups', 'team_members', 'work_teams_count'],
        'volunteers' => ['needs_volunteers', 'required_volunteers', 'volunteer_age_from', 'volunteer_age_to', 'volunteer_age_range', 'volunteer_need', 'volunteer_gender', 'volunteer_tasks_summary'],
        'official_correspondence' => ['needs_official_correspondence', 'official_correspondence_reason', 'official_correspondence_target', 'official_correspondence_brief'],
        'media_coverage' => ['needs_media_coverage', 'media_coverage_notes'],
        'supplies' => ['requires_supplies', 'supplies', 'supplies_count'],
        'official_sponsorship' => ['has_sponsor', 'sponsors', 'sponsors_count'],
        'external_partners' => ['has_partners', 'partners', 'partners_count'],
        'ceremony_agenda' => ['needs_ceremony_agenda', 'ceremony_items', 'ceremony_items_count', 'ceremony_time_from', 'ceremony_time_to', 'ceremony_item_name', 'ceremony_item_description'],
        'transport' => ['needs_transport', 'transport_vehicles_count', 'transport_vehicle_type', 'transport_passengers_count', 'transport_trip_direction', 'transport_start_from', 'transport_start_to'],
        'maintenance_workers' => ['needs_maintenance_workers', 'maintenance_type'],
        'gifts_shields' => ['needs_gifts', 'gifts_count', 'gifts_description', 'gifts_delivery_entity'],
        'programs_participation' => ['needs_programs_participation', 'programs_need_trainer', 'programs_trainer_description', 'programs_trainer_count', 'programs_zaha_time_options', 'programs_zaha_time_other', 'programs_show_name', 'programs_show_description', 'programs_fun_note', 'programs_needs_show', 'programs_needs_fun'],
        'certificates' => ['needs_certificates_details', 'certificates_count', 'certificates_template', 'certificates_for'],
        'thanks_letters' => ['needs_thanks_letters_details', 'thanks_letters_count', 'thanks_letters_template', 'thanks_letters_for'],
        'certificates_thanks' => ['needs_certificates_and_thanks'],
        'invitations' => ['needs_invitations', 'invitation_type', 'invitation_paper_copies', 'invitation_electronic_channels', 'invitation_paper_template', 'invitation_electronic_template'],
    ];

    public const MONTHLY_PAYLOAD_SECTIONS = [
        'ceremony_agenda' => 'ceremony', 'transport' => 'transport', 'maintenance_workers' => 'maintenance',
        'gifts_shields' => 'gifts', 'programs_participation' => 'programs', 'certificates' => 'certificates',
        'thanks_letters' => 'thanks_letters', 'invitations' => 'invitations',
    ];

    public static function rejectUnavailableMonthlyFields(array $input, array $codes): void
    {
        $errors = [];
        foreach (self::MONTHLY_INPUT_FIELDS as $code => $fields) {
            if (self::monthlyKeyAvailable($code, $codes)) continue;
            foreach ($fields as $field) {
                if (array_key_exists($field, $input)) $errors[$field] = 'هذا الاحتياج غير متاح للتعديل؛ بياناته السابقة محفوظة للقراءة فقط.';
            }
        }
        foreach (['need_availability', 'execution_needs_followup'] as $group) {
            if (isset($input[$group]) && ! is_array($input[$group])) {
                $errors[$group] = 'صيغة بيانات الاحتياجات غير صحيحة.';
                continue;
            }
            foreach ($input[$group] ?? [] as $key => $value) {
                $code = is_array($value) ? ($value['key'] ?? $key) : $key;
                if (! self::monthlyKeyAvailable((string) $code, $codes)) {
                    $errors[$group.'.'.$key] = 'هذا الاحتياج غير متاح للتعديل.';
                }
            }
        }
        if ($errors) throw \Illuminate\Validation\ValidationException::withMessages($errors);
    }

    // Existing monthly request keys are public contracts and remain unchanged.
    public const MONTHLY_FIELDS = [
        'volunteers' => 'needs_volunteers',
        'official_correspondence' => 'needs_official_correspondence',
        'media_coverage' => 'needs_media_coverage',
        'supplies' => 'requires_supplies',
        'official_sponsorship' => 'has_sponsor',
        'external_partners' => 'has_partners',
        'ceremony_agenda' => 'needs_ceremony_agenda',
        'transport' => 'needs_transport',
        'maintenance_workers' => 'needs_maintenance_workers',
        'gifts_shields' => 'needs_gifts',
        'programs_participation' => 'needs_programs_participation',
        'certificates_thanks' => 'needs_certificates_and_thanks',
        'invitations' => 'needs_invitations',
    ];

    public static function monthlyAvailableCodes(): array
    {
        $configured = static::query()->pluck('code');
        $available = static::query()->availableFor(EventSubjectTypes::MONTHLY_ACTIVITY)->pluck('code')->all();
        // Monthly forms predate the catalogue. Preserve their existing defaults
        // only for absent rows; an explicit inactive/none row always wins.
        foreach (self::CANONICAL_DEFINITIONS as $code => $definition) {
            if (($code === 'execution_team' || ($definition['monthly'] ?? true)) && ! $configured->contains($code)) {
                $available[] = $code;
            }
        }

        return $available;
    }

    public static function monthlyKeyAvailable(string $key, array $codes): bool
    {
        $mapped = self::mappingForLegacy($key)['canonical'] ?? [$key];

        return count(array_intersect($mapped, $codes)) > 0;
    }

    public static function validateMonthlySelection(array $data): void
    {
        $codes = static::monthlyAvailableCodes();
        $errors = [];
        foreach (self::MONTHLY_FIELDS as $code => $field) {
            if (! empty($data[$field]) && ! self::monthlyKeyAvailable($code, $codes)) {
                $errors[$field] = 'هذا الاحتياج غير متاح للخطط الشهرية.';
            }
        }
        foreach (['certificates' => 'needs_certificates_details', 'thanks_letters' => 'needs_thanks_letters_details'] as $code => $field) {
            if (! empty($data[$field]) && ! in_array($code, $codes, true)) {
                $errors[$field] = 'هذا الاحتياج غير متاح للخطط الشهرية.';
            }
        }
        if (! empty($data['supplies']) && ! in_array('supplies', $codes, true)) {
            $errors['supplies'] = 'هذا الاحتياج غير متاح للخطط الشهرية.';
        }
        if (! in_array('execution_team', $codes, true)) {
            $members = collect($data['team_members'] ?? [])->merge(collect($data['team_groups'] ?? [])->pluck('members')->flatten(1));
            if ($members->contains(fn ($member) => filled($member['member_name'] ?? null))) {
                $errors['team_groups'] = 'فريق التنفيذ غير متاح للخطط الشهرية.';
            }
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public static function usageScopes(): array
    {
        return [self::SCOPE_MONTHLY_PLANS, self::SCOPE_IFTARS, self::SCOPE_BOTH, self::SCOPE_NONE];
    }

    // Keep the existing public flags as the single storage representation.
    public function getUsageScopeAttribute(): string
    {
        if ($this->is_monthly_activity) {
            return $this->is_ramadan_iftar ? self::SCOPE_BOTH : self::SCOPE_MONTHLY_PLANS;
        }

        return $this->is_ramadan_iftar ? self::SCOPE_IFTARS : self::SCOPE_NONE;
    }

    public function setUsageScopeAttribute(string $scope): void
    {
        if (! in_array($scope, self::usageScopes(), true)) {
            throw new \InvalidArgumentException('Invalid execution need usage scope.');
        }

        $this->attributes['is_monthly_activity'] = in_array($scope, [self::SCOPE_MONTHLY_PLANS, self::SCOPE_BOTH], true);
        $this->attributes['is_ramadan_iftar'] = in_array($scope, [self::SCOPE_IFTARS, self::SCOPE_BOTH], true);
    }

    public const MAPPING_EXACT = 'EXACT';
    public const MAPPING_RENAMED_EQUIVALENT = 'RENAMED_EQUIVALENT';
    public const MAPPING_MERGED = 'MERGED';
    public const MAPPING_SPLIT = 'SPLIT';
    public const MAPPING_NO_SAFE_MAPPING = 'NO_SAFE_MAPPING';

    public const CANONICAL_DEFINITIONS = [
        'execution_team' => ['name' => 'فريق التنفيذ', 'monthly' => true, 'ramadan' => true, 'mandatory_monthly' => false, 'mandatory_ramadan' => true],
        'volunteers' => ['name' => 'الفرق التطوعية', 'monthly' => true, 'ramadan' => true],
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
        'scope_configured_at',
        'usage_scope',
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
        'is_canonical',
        'is_monthly_activity',
        'is_ramadan_iftar',
        'mandatory_for_monthly',
        'mandatory_for_ramadan',
    ];

    protected $casts = [
        'scope_configured_at' => 'datetime',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_canonical' => 'boolean',
        'is_monthly_activity' => 'boolean',
        'is_ramadan_iftar' => 'boolean',
        'mandatory_for_monthly' => 'boolean',
        'mandatory_for_ramadan' => 'boolean',
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

    public function scopeForMonthlyActivities($query)
    {
        return $query->where('is_monthly_activity', true);
    }

    public function isMandatoryForRamadan(): bool
    {
        return $this->is_ramadan_iftar && $this->mandatory_for_ramadan;
    }

    public function isMandatoryForMonthly(): bool
    {
        return $this->is_monthly_activity && $this->mandatory_for_monthly;
    }
}
