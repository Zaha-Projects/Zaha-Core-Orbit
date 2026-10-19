<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;

/**
 * Operational reports hub (تقارير تشغيلية). Events KPI reporting moves to the new KPI section in Phase 3.
 */
class ReportsController extends Controller
{
    public function index()
    {
        $reports = [
            ['route' => 'role.reports.finance.index', 'title' => __('app.roles.reports.finance.title'), 'description' => __('app.roles.reports.finance.subtitle'), 'icon' => 'fas fa-money-bill-trend-up'],
            ['route' => 'role.reports.maintenance.index', 'title' => __('app.roles.reports.maintenance.title'), 'description' => __('app.roles.reports.maintenance.subtitle'), 'icon' => 'fas fa-toolbox'],
            ['route' => 'role.reports.transport.index', 'title' => __('app.roles.reports.transport.title'), 'description' => __('app.roles.reports.transport.subtitle'), 'icon' => 'fas fa-truck-fast'],
        ];

        return view('pages.reports.index', compact('reports'));
    }
}
