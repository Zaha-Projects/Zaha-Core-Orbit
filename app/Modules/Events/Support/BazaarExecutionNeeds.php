<?php

namespace App\Modules\Events\Support;

use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\SubjectExecutionNeed;

/**
 * Bazaar execution-need rows (form ⇄ subject_execution_needs.planned_details).
 *
 * Non-invitation needs keep plain-text planned_details. The invitations need stores a JSON object;
 * keys unknown to this class are preserved on save, and the legacy single `distribution_channel`
 * (including the old `sponsored_ad` code) is read as `distribution_channels`.
 */
class BazaarExecutionNeeds
{
    public const INVITATIONS = 'invitations';

    public const CARD_TYPES = ['paper', 'electronic'];

    public const CHANNELS = ['whatsapp', 'facebook', 'instagram', 'paid_ad', 'other'];

    public const LEGACY_CHANNELS = ['sponsored_ad' => 'paid_ad'];

    public const INVITATION_KEYS = ['invitation_type', 'distribution_channels', 'distribution_other', 'paper_template', 'paper_copies', 'electronic_template'];

    private const LEGACY_INVITATION_KEYS = ['distribution_channel'];

    public const ICONS = [
        'volunteers' => 'fa-hands-helping',
        'official_correspondence' => 'fa-envelope-open-text',
        'media_coverage' => 'fa-camera',
        'supplies' => 'fa-boxes-stacked',
        'official_sponsorship' => 'fa-award',
        'external_partners' => 'fa-handshake',
        'ceremony_agenda' => 'fa-list-ol',
        'transport' => 'fa-bus',
        'maintenance_workers' => 'fa-screwdriver-wrench',
        'gifts_shields' => 'fa-gift',
        'programs_participation' => 'fa-masks-theater',
        'certificates' => 'fa-certificate',
        'thanks_letters' => 'fa-envelope-circle-check',
        'invitations' => 'fa-envelope',
    ];

    public const AVAILABILITY_VALUES = ['available', 'not_available'];

    /** Same rules as Monthly (config/execution_needs.php → center_availability). */
    public static function showsAvailabilityField(?string $code): bool
    {
        return (bool) config('execution_needs.center_availability.show_field', true)
            && ! in_array($code, (array) config('execution_needs.center_availability.forced_not_available', []), true);
    }

    public static function defaultAvailability(): string
    {
        $default = (string) config('execution_needs.center_availability.default');

        return in_array($default, self::AVAILABILITY_VALUES, true) ? $default : 'not_available';
    }

    /** Missing → Monthly default; forced/hidden → not_available; anything else is left for validation. */
    public static function availabilityFor(?string $code, $submitted)
    {
        if (! self::showsAvailabilityField($code)) {
            return 'not_available';
        }

        return $submitted === null || $submitted === '' ? self::defaultAvailability() : $submitted;
    }

    public static function icon(?string $code): string
    {
        return self::ICONS[$code] ?? 'fa-list-check';
    }

    public static function title(ExecutionNeedType $type): string
    {
        $key = 'bazaars.needs.titles.'.$type->code;

        return trans()->has($key) ? __($key) : $type->name;
    }

    /** Form row from a saved need (edit hydration and submit re-validation). */
    public static function fromStored(SubjectExecutionNeed $need): array
    {
        // Saved availability is restored as stored (pre-parity rows were all saved as `available`).
        $row = ['execution_need_type_id' => $need->execution_need_type_id, 'selected' => 1, 'availability' => $need->availability, 'planned_details' => $need->planned_details];
        if ($need->executionNeedType?->code !== self::INVITATIONS) {
            return $row;
        }

        $decoded = json_decode((string) $need->planned_details, true);

        return ['execution_need_type_id' => $need->execution_need_type_id, 'selected' => 1, 'availability' => $need->availability] + self::invitationFields(is_array($decoded) ? $decoded : []);
    }

    /**
     * Drop stale detail input before validation: unselected optional needs keep no details, and
     * invitation fields that do not apply to the chosen card type / channels are removed.
     */
    public static function normalizeRow(?ExecutionNeedType $type, array $row): array
    {
        $selected = (bool) ($row['selected'] ?? false) || (bool) $type?->isMandatoryForBazaar();
        $base = ['execution_need_type_id' => $row['execution_need_type_id'] ?? null, 'selected' => $selected ? 1 : 0];
        if (! $selected) {
            return $base;
        }
        $base['availability'] = self::availabilityFor($type?->code, $row['availability'] ?? null);
        if ($type?->code !== self::INVITATIONS) {
            return $base + ['planned_details' => $row['planned_details'] ?? null];
        }

        return $base + self::invitationFields($row);
    }

    /** planned_details value to persist, preserving unknown keys of the previously stored JSON. */
    public static function storageValue(ExecutionNeedType $type, array $row, ?string $previous = null): ?string
    {
        if ($type->code !== self::INVITATIONS) {
            return $row['planned_details'] ?? null;
        }

        $old = json_decode((string) $previous, true);
        $preserved = is_array($old) ? array_diff_key($old, array_flip(array_merge(self::INVITATION_KEYS, self::LEGACY_INVITATION_KEYS))) : [];
        $fields = array_filter(self::invitationFields($row), fn ($value) => $value !== null && $value !== '' && $value !== []);

        return json_encode($preserved + $fields, JSON_UNESCAPED_UNICODE);
    }

    /** Human-readable [label => value] lines for review/show pages. */
    public static function summary(ExecutionNeedType $type, array $row): array
    {
        $lines = [];
        if (in_array($row['availability'] ?? null, self::AVAILABILITY_VALUES, true)) {
            $lines[__('bazaars.needs.availability')] = __('bazaars.needs.availability_options.'.$row['availability']);
        }
        if ($type->code !== self::INVITATIONS) {
            return $lines + (filled($row['planned_details'] ?? null) ? [__('bazaars.needs.planned_details') => $row['planned_details']] : []);
        }

        if (filled($row['invitation_type'] ?? null)) {
            $lines[__('bazaars.invitation_cards.card_type')] = __('bazaars.invitation_cards.types.'.$row['invitation_type']);
        }
        if (($row['distribution_channels'] ?? []) !== []) {
            $lines[__('bazaars.invitation_cards.channels')] = collect($row['distribution_channels'])
                ->map(fn ($channel) => $channel === 'other' && filled($row['distribution_other'] ?? null)
                    ? __('bazaars.invitation_cards.channel_options.other').' ('.$row['distribution_other'].')'
                    : __('bazaars.invitation_cards.channel_options.'.$channel))
                ->join('، ');
        }
        foreach (['paper_template', 'paper_copies', 'electronic_template'] as $key) {
            if (filled($row[$key] ?? null)) {
                $lines[__('bazaars.invitation_cards.'.$key)] = $row[$key];
            }
        }

        return $lines;
    }

    private static function invitationFields(array $row): array
    {
        $type = $row['invitation_type'] ?? null;
        $channels = $row['distribution_channels'] ?? (isset($row['distribution_channel']) && $row['distribution_channel'] !== '' ? [$row['distribution_channel']] : []);
        $channels = array_values(array_unique(array_map(
            fn ($channel) => self::LEGACY_CHANNELS[$channel] ?? $channel,
            array_filter(is_array($channels) ? $channels : [$channels], fn ($channel) => is_string($channel) && $channel !== '')
        )));
        $electronic = $type === 'electronic';
        $paper = $type === 'paper';

        return [
            'invitation_type' => $type,
            'distribution_channels' => $electronic ? $channels : [],
            'distribution_other' => $electronic && in_array('other', $channels, true) ? ($row['distribution_other'] ?? null) : null,
            'paper_template' => $paper ? ($row['paper_template'] ?? null) : null,
            'paper_copies' => $paper ? ($row['paper_copies'] ?? null) : null,
            'electronic_template' => $electronic ? ($row['electronic_template'] ?? null) : null,
        ];
    }
}
