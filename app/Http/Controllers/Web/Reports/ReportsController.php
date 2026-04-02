<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->get();
        return view('pages.reports.index', compact('branches'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
