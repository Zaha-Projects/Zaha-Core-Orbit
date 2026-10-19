<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\MonthlyKpi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @deprecated KPI refactor Phase 2: manually entered monthly KPIs are retired. The page stays reachable read-only for historical rows; data entry (store) is no longer routed. Replaced by calculated KPIs in Phase 4. Do not drop monthly_kpis.
 */
class MonthlyKpisController extends Controller
{
    public function index()
    {
        $kpis = MonthlyKpi::with(['branch', 'creator'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('pages.reports.kpis', compact('kpis'));
    }

    /**
     * @deprecated Not routed since KPI refactor Phase 2 (manual KPI entry retired). Kept for historical compatibility.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canManageKpis($user), 403);

        // Branch-scoped users may only save against their own branches and never as "all branches".
        $isBranchScoped = $user->isBranchScopedPlanningUser();
        $scopedBranchIds = $user->scopedBranchIds();
        if ($isBranchScoped && ! $request->filled('branch_id') && count($scopedBranchIds) === 1) {
            $request->merge(['branch_id' => $scopedBranchIds[0]]);
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'branch_id' => $isBranchScoped
                ? ['required', 'integer', 'exists:branches,id', Rule::in($scopedBranchIds)]
                : ['nullable', 'integer', 'exists:branches,id'],
            'planned_activities_count' => ['required', 'integer', 'min:0'],
            'unplanned_activities_count' => ['required', 'integer', 'min:0'],
            'modification_rate_percent' => ['nullable', 'integer', 'between:0,100'],
            'plan_commitment_percent' => ['nullable', 'integer', 'between:0,100'],
            'mobilization_efficiency_percent' => ['nullable', 'integer', 'between:0,100'],
            'branch_monthly_score' => ['nullable', 'integer', 'between:0,100'],
            'followup_commitment_score' => ['nullable', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);

        MonthlyKpi::updateOrCreate(
            [
                'year' => $data['year'],
                'month' => $data['month'],
                'branch_id' => $data['branch_id'] ?? null,
            ],
            array_merge($data, ['created_by' => $user->id])
        );

        return redirect()->route('role.reports.kpis.index')->with('status', __('app.roles.reports.kpis.saved'));
    }

    /**
     * Mirrors the store route middleware (role_or_permission:followup_officer|super_admin|kpi.manage).
     */
    private function canManageKpis(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'followup_officer']) || $user->can('kpi.manage');
    }
}
