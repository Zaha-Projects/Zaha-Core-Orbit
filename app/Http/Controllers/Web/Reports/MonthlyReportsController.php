<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use Illuminate\Http\Request;

class MonthlyReportsController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('pages.reports.monthly', compact('branches', 'activities'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
