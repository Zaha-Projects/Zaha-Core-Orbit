<?php

namespace App\Http\Controllers\Roles\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivity;
use Illuminate\Http\Request;

class MonthlyReportsController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('roles.reports.monthly', compact('branches', 'centers', 'activities'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
