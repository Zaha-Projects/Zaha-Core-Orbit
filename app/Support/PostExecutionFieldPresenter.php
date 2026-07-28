<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

class PostExecutionFieldPresenter
{
    public static function label(string $key, ?string $fallback = null): string
    {
        if (preg_match('/^teams\.(\d+)\.(.+)$/', $key, $matches)) {
            $fieldKey = 'evaluation.verification.team_fields.' . $matches[2];
            $fieldLabel = __($fieldKey);

            return __('evaluation.verification.team_field', [
                'number' => ((int) $matches[1]) + 1,
                'field' => $fieldLabel === $fieldKey ? self::humanize($matches[2]) : $fieldLabel,
            ]);
        }

        if (preg_match('/^ceremony_items\.(\d+)\.(.+)$/', $key, $matches)) {
            $fieldKey = 'evaluation.verification.ceremony_fields.' . $matches[2];
            $fieldLabel = __($fieldKey);

            return __('evaluation.verification.ceremony_field', [
                'number' => ((int) $matches[1]) + 1,
                'field' => $fieldLabel === $fieldKey ? self::humanize($matches[2]) : $fieldLabel,
            ]);
        }

        $translationKey = 'evaluation.verification.post_execution_fields.' . $key;
        $translated = __($translationKey);

        return $translated === $translationKey
            ? ($fallback ?: self::humanize($key))
            : $translated;
    }

    public static function value(string $key, mixed $value): string
    {
        if (is_array($value)) {
            return $value === []
                ? __('evaluation.verification.no_data')
                : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—');
        }

        if (Str::endsWith($key, ['all_members_attended', 'was_implemented'])) {
            if ($value === null || $value === '') {
                return '—';
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true
                ? __('evaluation.verification.yes')
                : __('evaluation.verification.no');
        }

        if ($key === 'completed_at' && filled($value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i');
            } catch (\Throwable) {
                // Retain malformed legacy values instead of hiding them.
            }
        }

        return filled($value) || $value === 0 || $value === '0' ? (string) $value : '—';
    }

    protected static function humanize(string $key): string
    {
        return (string) Str::of($key)->replace(['.', '_'], ' ')->title();
    }
}
