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

    /** @return array<string, array> active KPI definitions keyed by code */
    public function definitions(?string $module = null): array
    {
        $definitions = [];
        foreach ((array) config('kpis.definitions', []) as $code => $definition) {
            if (! ($definition['active'] ?? false)) {
                continue;
            }
            if ($module !== null && ! in_array($module, $definition['applicable_modules'] ?? [], true)) {
                continue;
            }
            $definitions[$code] = ['code' => $code] + $definition;
        }

        return $definitions;
    }

    /** @return array<string, array> module metadata keyed by module key */
    public function modules(): array
    {
        return (array) config('kpis.modules', []);
    }

    public function hasModule(?string $key): bool
    {
        return $key !== null && array_key_exists($key, $this->modules());
    }

    public function module(string $key): array
    {
        return $this->modules()[$key];
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
}
