<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $requests = MaintenanceRequest::query()
            ->when($user->isBranchScopedPlanningUser(), fn ($query) => $query->whereIn('branch_id', $user->scopedBranchIds()))
            ->orderByDesc('logged_at')
            ->get();

        return view('pages.reports.maintenance', compact('requests'));
    }
}
