<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Modules\ActivityPlanning\MonthlyActivities\Presenters\MonthlyActivityCalendarEventPresenter;
use App\Modules\ActivityPlanning\MonthlyActivities\Queries\MonthlyActivitiesCalendarQuery;
use App\Modules\ActivityPlanning\MonthlyActivities\Queries\MonthlyActivityListFilters;
use Illuminate\Http\Request;

final class MonthlyActivityCalendarController extends Controller
{
    public function index(
        Request $request,
        MonthlyActivitiesCalendarQuery $calendarQuery,
        MonthlyActivityCalendarEventPresenter $presenter
    )
    {
        $queryFilters = MonthlyActivityListFilters::fromRequest($request);
        $year = $queryFilters->year;
        $month = $queryFilters->month;
        $viewScope = $queryFilters->viewScope;

        $items = $calendarQuery->build($request, $queryFilters)
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('proposed_date')
            ->get()
            ->map(fn (MonthlyActivity $activity): array => $presenter->present(
                $activity,
                $year,
                $request->user(),
                $viewScope
            ))
            ->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'items' => $items,
        ]);
    }
}
