<?php

namespace App\Http\Controllers\Web\Kpi\Concerns;

use App\Services\Kpi\KpiPeriod;
use App\Services\Kpi\KpiScope;
use Illuminate\Http\Request;

/**
 * Shared KPI filter validation (period, branch, module) for the KPI controllers.
 * Requires a $registry (KpiRegistry) property on the using controller.
 */
trait ParsesKpiFilters
{
    private function filterOptions(array $filters, ?array $moduleKeys = null): array
    {
        return [
            'years' => KpiPeriod::selectableYears(),
            'months' => KpiPeriod::MONTHS_AR,
            'branches' => $filters['scope']->availableBranches(),
            'moduleOptions' => collect($moduleKeys ?? array_keys($this->registry->modules()))
                ->mapWithKeys(fn (string $key): array => [$key => $this->registry->moduleLabel($key)])->all(),
        ];
    }

    /**
     * Server-side filter validation. Invalid values fall back to defaults and are reported;
     * a branch outside the viewer's scope is a 403 (KpiScope).
     *
     * @param  array<int, string>  $allowedModules  modules accepted by the "module" filter ([] = no module filter)
     */
    private function filters(Request $request, array $allowedModules, ?int $forcedBranchId = null, ?KpiScope $scope = null): array
    {
        $rejected = [];
        $read = function (string $key, int $min, int $max) use ($request, &$rejected): ?int {
            $raw = $request->query($key);
            if ($raw === null || $raw === '') {
                return null;
            }
            $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
            if ($value === false) {
                $rejected[] = $key;

                return null;
            }

            return (int) $value;
        };

        $year = $read('year', KpiPeriod::MIN_YEAR, KpiPeriod::maxYear()) ?? (int) now()->year;
        $month = $read('month', 1, 12);
        $branchId = $forcedBranchId ?? $read('branch_id', 1, PHP_INT_MAX);

        $module = null;
        if ($allowedModules !== []) {
            $raw = $request->query('module');
            if ($raw !== null && $raw !== '') {
                if (is_string($raw) && in_array($raw, $allowedModules, true)) {
                    $module = $raw;
                } else {
                    $rejected[] = 'module';
                }
            }
        }

        return [
            'period' => new KpiPeriod($year, $month),
            'scope' => $scope ?? KpiScope::forUser($request->user(), $branchId),
            'year' => $year,
            'month' => $month,
            'branch_id' => $branchId,
            'module' => $module,
            'rejected' => $rejected,
            'query' => array_filter(['year' => $year, 'month' => $month, 'branch_id' => $branchId, 'module' => $module], fn ($value) => $value !== null),
        ];
    }
}
