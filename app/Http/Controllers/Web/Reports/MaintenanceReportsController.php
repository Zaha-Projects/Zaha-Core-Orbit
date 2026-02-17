<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceReportsController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::orderByDesc('logged_at')->get();

        return view('pages.reports.maintenance', compact('requests'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
