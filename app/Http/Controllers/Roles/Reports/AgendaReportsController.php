<?php

namespace App\Http\Controllers\Roles\Reports;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\Center;
use Illuminate\Http\Request;

class AgendaReportsController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $events = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('roles.reports.agenda', compact('branches', 'centers', 'events'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
