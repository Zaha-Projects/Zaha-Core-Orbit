<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EventCategory;
use App\Models\MonthlyActivity;
use App\Services\EnterpriseAnalyticsService;
use Illuminate\Http\Request;

class EnterpriseDashboardController extends Controller
{
    public function index(Request $request, EnterpriseAnalyticsService $analyticsService)
    {
        $year = (int) ($request->input('year', now()->year));
        $filters = $request->only(['year', 'month', 'department_id', 'event_category_id', 'status', 'branch_id', 'plan_type', 'event_type', 'archived']);

        $analytics = $analyticsService->build($year);
        $branchMetrics = $analyticsService->branchMetrics($year);

        $annualOverview = AgendaEvent::query()
            ->enterpriseFilter($filters)
            ->notArchived()
            ->with('participations')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        return view('pages.enterprise.dashboard', [
            'analytics' => $analytics,
            'branchMetrics' => $branchMetrics,
            'annualOverview' => $annualOverview,
            'years' => range(now()->year - 2, now()->year + 1),
            'departments' => Department::orderBy('name')->get(),
            'categories' => EventCategory::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function branchReport(Request $request, EnterpriseAnalyticsService $analyticsService)
    {
        $year = (int) ($request->input('year', now()->year));

        return view('pages.reports.enterprise.branch-performance', [
            'rows' => $analyticsService->branchMetrics($year),
            'year' => $year,
        ]);
    }

    public function annualPlanning(Request $request)
    {
        $year = (int) ($request->input('year', now()->year));

        $events = AgendaEvent::query()->whereYear('event_date', $year)->notArchived()->with('participations')->orderBy('event_date')->get()->groupBy('month');
        $activities = MonthlyActivity::query()->whereYear('proposed_date', $year)->notArchived()->get()->groupBy('month');

        return view('pages.enterprise.annual-planning-overview', compact('events', 'activities', 'year'));
    }
}
