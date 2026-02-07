<?php

namespace App\Http\Controllers\Roles\Reports;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class TransportReportsController extends Controller
{
    public function index()
    {
        $trips = Trip::orderByDesc('trip_date')->get();

        return view('roles.reports.transport', compact('trips'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
