<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Modules\ActivityPlanning\MonthlyActivities\Queries\MonthlyActivitiesIndexQuery;
use App\Modules\ActivityPlanning\MonthlyActivities\Queries\MonthlyActivityListFilters;
use App\Modules\ActivityPlanning\MonthlyActivities\Queries\MonthlyActivityVisibilityQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class MonthlyActivitiesBrowseController extends Controller
{
    private const EDIT_ROLES = [
        'relations_manager',
        'relations_officer',
        'supervisor',
        'relations_officer',
        'followup_officer',
        'evaluation_officer',
        'volunteer_coordinator',
        'branch_coordinator',
        'communication_head',
        'transport_officer',
        'movement_manager',
        'administrative_unit_manager',
        'super_admin',
    ];

    public function index(
        Request $request,
        MonthlyActivitiesIndexQuery $indexQuery,
        MonthlyActivityVisibilityQuery $visibilityQuery
    )
    {
        $user = $request->user();
        $queryFilters = MonthlyActivityListFilters::fromRequest($request);
        $viewScope = $queryFilters->viewScope;
        $selectedStatus = $queryFilters->status;
        $selectedBranchId = $queryFilters->branchId;
        $selectedSummaryFilter = $queryFilters->summaryFilter;
        $selectedYear = $queryFilters->year;
        $selectedMonth = $queryFilters->month;
        $showDeleted = $queryFilters->showDeleted;
        $activitiesBaseQuery = $indexQuery->build($request, $queryFilters);

        $deletedActivitiesCount = (clone $activitiesBaseQuery)->toBase()->cloneWithout(['orders', 'limit', 'offset'])->count();
        if (! $showDeleted) {
            $deletedActivitiesCount = $indexQuery->buildDeletedCount($queryFilters, $user)->count();
        }

        $summaryCards = $showDeleted ? collect() : $indexQuery->summaryCards($activitiesBaseQuery);
        $indexQuery->applySummaryFilter($activitiesBaseQuery, $selectedSummaryFilter);

        $perPage = $queryFilters->perPage;
        $activities = (clone $activitiesBaseQuery)
            ->with(['branch', 'agendaEvent', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->paginate($perPage)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $visibilityQuery->scopedBranchIds($user);
        $ownBranchId = $visibilityQuery->ownBranchId($user);
        if ($scopedBranchIds !== [] && $viewScope !== 'all_branches') {
            $branches->where('id', $ownBranchId);
        }
        if ($viewScope === 'all_branches' && $ownBranchId) {
            $branches->where('id', '!=', $ownBranchId);
        }

        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $filters = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'status' => $selectedStatus,
            'branch_id' => $selectedBranchId,
            'summary_filter' => $selectedSummaryFilter,
            'deleted' => $showDeleted,
            'per_page' => $perPage,
        ];
        $selectedMonthDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $previousMonthQuery = collect($request->except(['page', 'year', 'month', 'per_page']))
            ->put('year', $selectedMonthDate->copy()->subMonthNoOverflow()->year)
            ->put('month', $selectedMonthDate->copy()->subMonthNoOverflow()->month)
            ->all();
        $nextMonthQuery = collect($request->except(['page', 'year', 'month', 'per_page']))
            ->put('year', $selectedMonthDate->copy()->addMonthNoOverflow()->year)
            ->put('month', $selectedMonthDate->copy()->addMonthNoOverflow()->month)
            ->all();
        $canFilterBranches = $viewScope === 'all_branches'
            ? $visibilityQuery->canViewOtherBranches($user)
            : ($scopedBranchIds === []);
        $monthlyStatusOptions = collect([
            (object) ['code' => 'draft', 'name' => __('app.roles.programs.monthly_activities.statuses.draft')],
            (object) ['code' => 'submitted', 'name' => __('app.roles.programs.monthly_activities.statuses.submitted')],
            (object) ['code' => 'post_execution_submitted', 'name' => 'بانتظار اعتماد رئيس الفرع لما بعد التنفيذ'],
            (object) ['code' => 'approved', 'name' => __('app.roles.programs.monthly_activities.statuses.approved')],
        ]);
        $monthlyActivityEditRoles = self::EDIT_ROLES;
        $monthlyActivityChangeRequestRoles = array_values(array_filter((array) config(
            'monthly_activity.change_requests.allowed_roles',
            ['relations_officer']
        )));

        return view('pages.monthly_activities.activities.index', compact(
            'activities',
            'branches',
            'agendaEvents',
            'filters',
            'canFilterBranches',
            'viewScope',
            'monthlyStatusOptions',
            'summaryCards',
            'monthlyActivityEditRoles',
            'monthlyActivityChangeRequestRoles',
            'selectedMonthDate',
            'previousMonthQuery',
            'nextMonthQuery',
            'showDeleted',
            'deletedActivitiesCount',
        ));
    }
}
