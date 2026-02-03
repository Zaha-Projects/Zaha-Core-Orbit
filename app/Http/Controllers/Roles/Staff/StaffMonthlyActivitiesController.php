<?php

namespace App\Http\Controllers\Roles\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffMonthlyActivitiesController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $query = MonthlyActivity::with('branch')->orderBy('month')->orderBy('day');

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'));
            $query->where('month', $date->format('m'))
                ->where('day', $date->format('d'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $activities = $query->get();

        return view('roles.staff.activities', compact('activities', 'branches'));
    }
}
