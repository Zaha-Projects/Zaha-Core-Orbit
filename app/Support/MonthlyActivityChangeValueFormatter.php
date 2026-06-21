<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class MonthlyActivityChangeValueFormatter
{
    protected const EMPTY_HTML = '<span class="approval-change-empty">-</span>';

    protected const EXECUTION_NEED_SECTION_LABELS = [
        'availability' => 'توفر الاحتياجات داخل المركز',
        'volunteers' => 'المتطوعون',
        'official_correspondence' => 'المخاطبات الرسمية',
        'media_coverage' => 'التغطية الإعلامية',
        'supplies' => 'المستلزمات',
        'official_sponsorship' => 'الرعاية الرسمية',
        'external_partners' => 'الشركاء الخارجيون',
        'ceremony' => 'أجندة الحفل',
        'transport' => 'المواصلات',
        'maintenance' => 'عمال الصيانة',
        'maintenance_workers' => 'عمال الصيانة',
        'gifts' => 'الهدايا والدروع',
        'programs' => 'مشاركة البرامج',
        'certificates' => 'الشهادات',
        'thanks_letters' => 'كتب الشكر',
        'invitations' => 'بطاقات الدعوة',
    ];

    protected const VALUE_LABELS = [
        'available' => 'متوفر داخل المركز',
        'not_available' => 'غير متوفر داخل المركز',
        'provided' => 'تم التأمين',
        'not_provided' => 'لم يتم التأمين',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'pending' => 'قيد الانتظار',
        'male' => 'ذكور',
        'female' => 'إناث',
        'both' => 'ذكور وإناث',
        'one_way' => 'اتجاه واحد',
        'round_trip' => 'ذهاب وعودة',
        'storytelling' => 'سرد قصصي',
        'arts' => 'فنون',
        'sports' => 'رياضة',
        'games' => 'ألعاب',
        'music' => 'موسيقى',
        'theater' => 'مسرح',
        'availability' => 'توفر الاحتياجات داخل المركز',
        'volunteers' => 'المتطوعون',
        'official_correspondence' => 'المخاطبات الرسمية',
        'media_coverage' => 'التغطية الإعلامية',
        'supplies' => 'المستلزمات',
        'official_sponsorship' => 'الرعاية الرسمية',
        'external_partners' => 'الشركاء الخارجيون',
        'ceremony' => 'أجندة الحفل',
        'transport' => 'المواصلات',
        'maintenance' => 'عمال الصيانة',
        'maintenance_workers' => 'عمال الصيانة',
        'gifts' => 'الهدايا والدروع',
        'programs' => 'مشاركة البرامج',
        'certificates' => 'الشهادات',
        'thanks_letters' => 'كتب الشكر',
        'invitations' => 'بطاقات الدعوة',
    ];

    protected const FIELD_LABELS = [
        'count' => 'العدد',
        'need_code' => 'رمز الاحتياج',
        'description' => 'الوصف',
        'delivery_entity' => 'جهة التوفير',
        'future_cycle_id' => 'دورة لاحقة',
        'items' => 'العناصر',
        'items_count' => 'عدد الفقرات',
        'item_name' => 'اسم الفقرة',
        'item_description' => 'وصف الفقرة',
        'name' => 'الاسم',
        'time' => 'الوقت',
        'time_from' => 'وقت البداية',
        'time_to' => 'وقت النهاية',
        'notes' => 'ملاحظات',
        'status' => 'الحالة',
        'enabled' => 'مطلوب',
        'availability' => 'التوفر داخل المركز',
        'vehicles_count' => 'عدد المركبات',
        'vehicle_type' => 'نوع المركبة',
        'passengers_count' => 'عدد الركاب',
        'trip_direction' => 'اتجاه الرحلة',
        'start_from' => 'نقطة الانطلاق',
        'start_to' => 'نقطة الوصول',
        'type' => 'النوع',
        'need_trainer' => 'يحتاج مدرب',
        'trainer_description' => 'وصف المدرب',
        'trainer_count' => 'عدد المدربين',
        'zaha_time_other' => 'وقت زها - أخرى',
        'show_name' => 'اسم العرض',
        'show_description' => 'وصف العرض',
        'fun_note' => 'ملاحظة النشاط الترفيهي',
        'template' => 'النموذج',
        'for' => 'مخصصة لـ',
        'paper_template' => 'نموذج الدعوة الورقية',
        'paper_copies' => 'عدد النسخ الورقية',
        'electronic_template' => 'نموذج الدعوة الإلكترونية',
        'execution_needs_payload' => 'تفاصيل الاحتياجات التنفيذية',
        'post_status' => 'حالة ما بعد التنفيذ',
        'post_feedback' => 'ملاحظات ما بعد التنفيذ',
        'decision_by_name' => 'صاحب القرار',
        'decision_by_role' => 'دور صاحب القرار',
        'volunteer_count' => 'عدد المتطوعين',
        'volunteer_age_from' => 'العمر من',
        'volunteer_age_to' => 'العمر إلى',
        'volunteer_gender' => 'الجنس',
        'volunteer_tasks_summary' => 'مهام المتطوعين',
        'zaha_time_options' => 'خيارات وقت زها',
        'schema_version' => 'إصدار النموذج',
        'needs_registry' => 'سجل الاحتياجات',
        'needs_ceremony_agenda' => 'يحتاج أجندة حفل',
        'needs_transport' => 'يحتاج مواصلات',
        'needs_maintenance_workers' => 'يحتاج عمال صيانة',
        'needs_gifts' => 'يحتاج هدايا ودروع',
        'needs_programs_participation' => 'يحتاج مشاركة البرامج',
        'needs_certificates_and_thanks' => 'يحتاج شهادات وكتب شكر',
        'needs_invitations' => 'يحتاج بطاقات دعوة',
    ];

    protected const HIDDEN_TECHNICAL_FIELDS = [
        'need_code',
        'future_cycle_id',
    ];

    protected const NEED_FLAG_TO_SECTION = [
        'needs_ceremony_agenda' => 'ceremony',
        'needs_transport' => 'transport',
        'needs_maintenance_workers' => 'maintenance',
        'needs_gifts' => 'gifts',
        'needs_programs_participation' => 'programs',
        'needs_certificates_and_thanks' => 'certificates',
        'needs_invitations' => 'invitations',
    ];

    public static function format(mixed $value, string $field): HtmlString
    {
        if (self::isEmpty($value)) {
            return new HtmlString(self::EMPTY_HTML);
        }

        if (is_bool($value)) {
            return new HtmlString(self::booleanPill($value));
        }

        if (is_numeric($value) && self::isBooleanField($field)) {
            return new HtmlString(self::booleanPill((int) $value === 1));
        }

        if (is_array($value)) {
            return new HtmlString(self::formatArray($value, $field));
        }

        return new HtmlString('<span>'.e(self::stringValue($value)).'</span>');
    }

    public static function fieldLabelForDisplay(string $field): string
    {
        return self::fieldLabel($field);
    }

    protected static function formatArray(array $value, string $field): string
    {
        $items = $field === 'execution_needs_payload'
            ? self::executionNeedsItems($value)
            : self::arrayItems($value);

        if ($items === []) {
            return '<span class="approval-change-empty">لا توجد تفاصيل مدخلة</span>';
        }

        return '<ul class="approval-change-list mb-0">'.implode('', $items).'</ul>';
    }

    protected static function executionNeedsItems(array $payload): array
    {
        $items = [];
        $enabledSections = self::enabledExecutionNeedSections($payload);
        $sections = array_values(array_unique(array_merge(array_keys($payload), $enabledSections)));

        foreach ($sections as $section) {
            $section = (string) $section;
            $details = $payload[$section] ?? [];
            $isEnabled = in_array($section, $enabledSections, true);

            if (self::shouldHideExecutionNeedSection($section, $details, $isEnabled, array_key_exists($section, $payload))) {
                continue;
            }

            $label = self::sectionLabel($section);
            $summary = is_array($details)
                ? self::nestedSummary($details)
                : self::stringValue($details);

            if (self::isEmptyText($summary)) {
                if (! $isEnabled && ! array_key_exists($section, $payload)) {
                    continue;
                }

                $summary = e('لا توجد تفاصيل مدخلة');
            }

            $items[] = '<li><strong>'.e($label).':</strong> '.$summary.'</li>';
        }

        return $items;
    }

    protected static function arrayItems(array $value): array
    {
        if (array_is_list($value)) {
            return collect($value)
                ->reject(fn ($item): bool => self::isEmpty($item) || self::isNonDisplayValue($item))
                ->map(fn ($item): string => '<li>'.self::nestedSummary($item).'</li>')
                ->reject(fn (string $item): bool => self::isEmptyText($item))
                ->values()
                ->all();
        }

        $items = [];

        foreach ($value as $key => $item) {
            if (in_array((string) $key, self::HIDDEN_TECHNICAL_FIELDS, true)) {
                continue;
            }

            if (self::isEmpty($item) || self::isNonDisplayValue($item)) {
                continue;
            }

            $items[] = '<li><strong>'.e(self::fieldLabel((string) $key)).':</strong> '.self::nestedSummary($item).'</li>';
        }

        return $items;
    }

    protected static function nestedSummary(mixed $value, bool $hideDefaults = false): string
    {
        if ($hideDefaults && self::isNonDisplayValue($value)) {
            return '';
        }

        if (is_bool($value)) {
            return e($value ? 'نعم' : 'لا');
        }

        if (! is_array($value)) {
            return e(self::stringValue($value));
        }

        if (array_is_list($value)) {
            $parts = collect($value)
                ->reject(fn ($item): bool => self::isEmpty($item) || ($hideDefaults && self::isNonDisplayValue($item)))
                ->map(fn ($item): string => strip_tags(self::nestedSummary($item, $hideDefaults)))
                ->filter()
                ->values();

            return e($parts->isNotEmpty() ? $parts->implode('، ') : '');
        }

        $parts = [];

        foreach ($value as $key => $item) {
            if (in_array((string) $key, self::HIDDEN_TECHNICAL_FIELDS, true)) {
                continue;
            }

            if (self::isEmpty($item) || ($hideDefaults && self::isNonDisplayValue($item))) {
                continue;
            }

            $summary = strip_tags(self::nestedSummary($item, $hideDefaults));
            if ($summary === '') {
                continue;
            }

            $parts[] = self::fieldLabel((string) $key).': '.$summary;
        }

        return e($parts !== [] ? implode('، ', $parts) : '');
    }

    protected static function stringValue(mixed $value): string
    {
        $text = (string) $value;

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $text)) {
            return substr($text, 0, 10);
        }

        return self::VALUE_LABELS[$text] ?? $text;
    }

    protected static function booleanPill(bool $value): string
    {
        return '<span class="approval-change-pill">'.($value ? 'نعم' : 'لا').'</span>';
    }

    protected static function isBooleanField(string $field): bool
    {
        return str_starts_with($field, 'needs_')
            || str_starts_with($field, 'is_')
            || str_starts_with($field, 'requires_');
    }

    protected static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    protected static function isEmptyText(string $html): bool
    {
        return trim(strip_tags($html)) === '';
    }

    protected static function isNonDisplayValue(mixed $value): bool
    {
        return $value === false || $value === 0 || $value === '0' || $value === 'not_available';
    }

    protected static function shouldHideExecutionNeedSection(string $section, mixed $details, bool $isEnabled, bool $existsInPayload): bool
    {
        return in_array($section, self::HIDDEN_TECHNICAL_FIELDS, true)
            || (! $existsInPayload && ! $isEnabled && self::isEmpty($details));
    }

    /**
     * @return array<int, string>
     */
    protected static function enabledExecutionNeedSections(array $payload): array
    {
        $sections = [];

        foreach (self::NEED_FLAG_TO_SECTION as $flag => $section) {
            if ((bool) ($payload[$flag] ?? false)) {
                $sections[] = $section;

                if ($section === 'certificates') {
                    $sections[] = 'thanks_letters';
                }
            }
        }

        foreach ((array) ($payload['needs_registry'] ?? []) as $section => $meta) {
            if ((bool) data_get($meta, 'enabled', false)) {
                $sections[] = (string) $section;
            }
        }

        return array_values(array_unique($sections));
    }

    protected static function sectionLabel(string $key): string
    {
        return self::EXECUTION_NEED_SECTION_LABELS[$key] ?? self::fieldLabel($key);
    }

    protected static function fieldLabel(string $key): string
    {
        return self::FIELD_LABELS[$key]
            ?? self::EXECUTION_NEED_SECTION_LABELS[$key]
            ?? 'تفصيل إضافي';
    }
}
