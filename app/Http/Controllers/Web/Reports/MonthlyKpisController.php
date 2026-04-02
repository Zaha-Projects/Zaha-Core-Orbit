<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MonthlyKpi;
use Illuminate\Http\Request;

class MonthlyKpisController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $kpis = MonthlyKpi::with(['branch', 'creator'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('pages.reports.kpis', compact('kpis', 'branches'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('followup_officer'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'center_id' => ['nullable'],
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
                'center_id' => null,
            ],
            array_merge($data, ['created_by' => $request->user()->id])
        );

        return redirect()->route('role.reports.kpis.index')->with('status', __('app.roles.reports.kpis.saved'));
    }
}
