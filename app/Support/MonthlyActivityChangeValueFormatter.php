<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
    ];

    protected const FIELD_LABELS = [
        'count' => 'العدد',
        'need_code' => 'رمز الاحتياج',
        'description' => 'الوصف',
        'delivery_entity' => 'جهة التوفير',
        'future_cycle_id' => 'دورة لاحقة',
        'items' => 'العناصر',
        'notes' => 'ملاحظات',
        'status' => 'الحالة',
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
    ];

    protected const HIDDEN_TECHNICAL_FIELDS = [
        'need_code',
        'future_cycle_id',
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

        foreach ($payload as $section => $details) {
            if (self::isEmpty($details)) {
                continue;
            }

            $label = self::sectionLabel((string) $section);
            $summary = is_array($details)
                ? self::nestedSummary($details)
                : self::stringValue($details);

            $items[] = '<li><strong>'.e($label).':</strong> '.$summary.'</li>';
        }

        return $items;
    }

    protected static function arrayItems(array $value): array
    {
        if (array_is_list($value)) {
            return collect($value)
                ->reject(fn ($item): bool => self::isEmpty($item))
                ->map(fn ($item): string => '<li>'.self::nestedSummary($item).'</li>')
                ->values()
                ->all();
        }

        $items = [];

        foreach ($value as $key => $item) {
            if (in_array((string) $key, self::HIDDEN_TECHNICAL_FIELDS, true)) {
                continue;
            }

            if (self::isEmpty($item)) {
                continue;
            }

            $items[] = '<li><strong>'.e(self::fieldLabel((string) $key)).':</strong> '.self::nestedSummary($item).'</li>';
        }

        return $items;
    }

    protected static function nestedSummary(mixed $value): string
    {
        if (is_bool($value)) {
            return e($value ? 'نعم' : 'لا');
        }

        if (! is_array($value)) {
            return e(self::stringValue($value));
        }

        if (array_is_list($value)) {
            $parts = collect($value)
                ->reject(fn ($item): bool => self::isEmpty($item))
                ->map(fn ($item): string => strip_tags(self::nestedSummary($item)))
                ->filter()
                ->values();

            return e($parts->isNotEmpty() ? $parts->implode('، ') : 'لا توجد تفاصيل مدخلة');
        }

        $parts = [];

        foreach ($value as $key => $item) {
            if (in_array((string) $key, self::HIDDEN_TECHNICAL_FIELDS, true)) {
                continue;
            }

            if (self::isEmpty($item)) {
                continue;
            }

            $parts[] = self::fieldLabel((string) $key).': '.strip_tags(self::nestedSummary($item));
        }

        return e($parts !== [] ? implode('، ', $parts) : 'لا توجد تفاصيل مدخلة');
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

    protected static function sectionLabel(string $key): string
    {
        return self::EXECUTION_NEED_SECTION_LABELS[$key] ?? self::fieldLabel($key);
    }

    protected static function fieldLabel(string $key): string
    {
        return self::FIELD_LABELS[$key]
            ?? self::EXECUTION_NEED_SECTION_LABELS[$key]
            ?? (string) Str::of($key)->replace('_', ' ')->title();
    }
}
