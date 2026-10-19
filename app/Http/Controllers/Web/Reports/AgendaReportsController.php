<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\AgendaEvent;
use App\Models\Branch;
use Illuminate\Http\Request;

/**
 * @deprecated KPI refactor Phase 2: no longer routed; /dashboard/reports/agenda redirects to the operational reports hub. Replaced by the Annual Agenda KPI drill-down in Phase 7; delete in Phase 9.
 */
class AgendaReportsController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $events = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('pages.reports.agenda', compact('branches', 'events'));
    }

}
