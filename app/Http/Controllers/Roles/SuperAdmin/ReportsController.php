<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AdminReports\AdminReportsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request, AdminReportsService $reportsService)
    {
        $reportYear = (int) $request->input('report_year', now()->year);
        $reportMonth = (int) $request->input('report_month', now()->month);
        $activeTab = (string) $request->input('tab', config('admin_reports.relations.default_tab'));
        $availableTabs = config('admin_reports.relations.available_tabs', []);

        if (! array_key_exists($activeTab, $availableTabs)) {
            $activeTab = (string) config('admin_reports.relations.default_tab');
        }

        $reportData = $reportsService->build($reportYear, $reportMonth);
        $cacheConfig = $reportsService->cacheConfig();

        return view('pages.admin.reports.index', compact(
            'reportData',
            'reportYear',
            'reportMonth',
            'activeTab',
            'availableTabs',
            'cacheConfig'
        ));
    }
}
