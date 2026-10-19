<?php

namespace App\Services\Kpi;

/**
 * Result contract for one calculated KPI. Views read these properties; they never compute.
 *
 * value is null whenever the data cannot support the KPI, never a substitute 0.
 */
class KpiResult
{
    public const STATE_ACHIEVED = 'achieved';
    public const STATE_NOT_ACHIEVED = 'not_achieved';
    public const STATE_NO_TARGET = 'no_target';
    public const STATE_INFORMATIONAL = 'informational';
    public const STATE_INSUFFICIENT = 'insufficient_data';

    public string $code;
    public string $name;
    public string $description;
    public string $icon;
    public string $unit;

    /** @var float|int|null */
    public $value;
    public string $formattedValue;
    /** @var float|int|null */
    public $numerator;
    /** @var float|int|null */
    public $denominator;

    /** @var float|int|null */
    public $target;
    public ?string $formattedTarget;
    public string $targetSource;
    public bool $configurationRequired;
    public ?bool $achieved;
    public string $state;

    public string $period;
    public string $comparisonPeriod;
    /** @var array{direction: string, delta: float, formatted: string, previous: string}|null */
    public ?array $trend;

    public bool $dataAvailable;
    public ?string $missingReason;
    /** @var array<int, string> modules the value covers / excludes */
    public array $notes;
    /** @var array<string, array{label: string, value: int|float|null, formatted: string, reason: ?string}> */
    public array $breakdown;
    /** @var array{route: string, parameters: array}|null */
    public ?array $drilldown;

    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
