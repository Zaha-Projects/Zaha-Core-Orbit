<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Modules\Events\Models\MonthlyActivity;
use Illuminate\Http\Request;

/**
 * @deprecated KPI refactor Phase 2: no longer routed; /dashboard/reports/monthly redirects to the operational reports hub. Replaced by the Monthly Activities KPI drill-down in Phase 7; delete in Phase 9.
 */
class MonthlyReportsController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('pages.reports.monthly', compact('branches', 'activities'));
    }

}
