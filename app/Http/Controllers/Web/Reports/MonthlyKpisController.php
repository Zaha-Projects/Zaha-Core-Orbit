<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\MonthlyKpi;

/**
 * Legacy, read-only: historical manually entered monthly KPIs (monthly_kpis). Manual entry was retired
 * (Phase 2) and its write path removed (Phase 6); calculated KPIs live under /dashboard/kpi.
 * The table and its rows are preserved.
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
}
