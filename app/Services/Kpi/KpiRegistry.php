<?php

namespace App\Services\Kpi;

use App\Services\Kpi\Modules\KpiModuleDefinition;

/**
 * Read access to config/kpis.php.
 */
class KpiRegistry
{
    /** @var array<string, KpiModuleDefinition> */
    private array $moduleDefinitions = [];

    /**
     * Active KPI definitions keyed by code.
     *
     * @param  string|null  $page  overview | module | branch
     * @param  string|null  $module  for page "module": the module key
     */
    public function definitions(?string $page = null, ?string $module = null): array
    {
        $definitions = [];
        foreach ((array) config('kpis.definitions', []) as $code => $definition) {
            if (! ($definition['active'] ?? false)) {
                continue;
            }
            if ($page !== null && ($definition['page'] ?? null) !== $page) {
                continue;
            }
            if ($module !== null && ($definition['module'] ?? null) !== $module) {
                continue;
            }
            $definitions[$code] = ['code' => $code] + $definition;
        }

        return $definitions;
    }

    public function definition(string $code): ?array
    {
        $definition = config('kpis.definitions.'.$code);

        return is_array($definition) ? ['code' => $code] + $definition : null;
    }

    /** @return array<string, array> module metadata keyed by module key */
    public function modules(): array
    {
        return (array) config('kpis.modules', []);
    }

    /** @return array<int, string> modules whose records are branch-owned events */
    public function branchModules(): array
    {
        return (array) config('kpis.branch_modules', []);
    }

    public function hasModule(?string $key): bool
    {
        return is_string($key) && array_key_exists($key, $this->modules());
    }

    public function module(string $key): array
    {
        return $this->modules()[$key];
    }

    public function moduleLabel(string $key): string
    {
        return $this->modules()[$key]['label_ar'] ?? $key;
    }

    public function moduleDefinition(string $key): KpiModuleDefinition
    {
        if (! isset($this->moduleDefinitions[$key])) {
            $class = $this->module($key)['definition'];
            $this->moduleDefinitions[$key] = new $class();
        }

        return $this->moduleDefinitions[$key];
    }

    /** @return array<string, array> */
    public function stages(): array
    {
        return (array) config('kpis.stages', []);
    }

    public function categories(): array
    {
        return (array) config('kpis.categories', []);
    }

    public function statuses(): array
    {
        return (array) config('kpis.statuses', []);
    }

    /**
     * Metric names a set of definitions needs (always includes "records" for trend comparability).
     *
     * @return array<int, string>
     */
    public function metricsFor(array $definitions): array
    {
        $metrics = ['records' => true];
        foreach ($definitions as $definition) {
            $calculator = $definition['calculator'] ?? null;
            if (! $calculator) {
                continue;
            }
            foreach (['numerator', 'denominator'] as $part) {
                if (! empty($calculator[$part])) {
                    $metrics[$calculator[$part]] = true;
                }
            }
            foreach ($definition['availability'] ?? [] as $spec) {
                if (is_array($spec)) {
                    foreach (['numerator', 'denominator'] as $part) {
                        if (! empty($spec[$part])) {
                            $metrics[$spec[$part]] = true;
                        }
                    }
                }
            }
        }

        return array_keys($metrics);
    }
}
