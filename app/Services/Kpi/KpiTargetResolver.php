<?php

namespace App\Services\Kpi;

/**
 * Resolves a KPI target and evaluates achievement.
 *
 * Only an approved ("configured") target produces an achieved / not-achieved verdict.
 * A "proposed" target is displayed but never evaluated. No target means
 * configuration_required when the KPI has a comparison operator.
 */
class KpiTargetResolver
{
    public const SOURCE_CONFIGURED = 'configured';
    public const SOURCE_PROPOSED = 'proposed';
    public const SOURCE_NONE = 'none';

    /** @return array{target: float|int|null, source: string, operator: ?string, configuration_required: bool} */
    public function resolve(array $definition): array
    {
        $operator = $definition['comparison_operator'] ?? null;
        $target = $definition['target'] ?? null;
        $source = $target === null ? self::SOURCE_NONE : ($definition['target_source'] ?? self::SOURCE_PROPOSED);

        return [
            'target' => $target,
            'source' => $source,
            'operator' => $operator,
            'configuration_required' => $operator !== null && $source !== self::SOURCE_CONFIGURED,
        ];
    }

    public function achieved(?float $value, array $target): ?bool
    {
        if ($value === null || $target['source'] !== self::SOURCE_CONFIGURED || $target['operator'] === null) {
            return null;
        }

        switch ($target['operator']) {
            case '>=':
                return $value >= $target['target'];
            case '<=':
                return $value <= $target['target'];
            case '=':
                return abs($value - (float) $target['target']) < 0.00001;
        }

        return null;
    }
}
