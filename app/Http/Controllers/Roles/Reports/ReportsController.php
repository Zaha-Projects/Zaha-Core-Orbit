<?php

namespace App\Http\Controllers\Roles\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('roles.reports.index', compact('branches', 'centers'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
