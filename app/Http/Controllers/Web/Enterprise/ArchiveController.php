<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\MonthlyActivity;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function archiveYear(Request $request)
    {
        $year = (int) $request->validate(['year' => ['required', 'integer']])['year'];

        AgendaEvent::query()->whereYear('event_date', $year)->update(['is_archived' => true, 'archived_year' => $year]);
        MonthlyActivity::query()->whereYear('proposed_date', $year)->whereNotNull('actual_date')->update(['is_archived' => true, 'archived_year' => $year]);

        return back()->with('status', "Archived records for {$year}");
    }

    public function restoreYear(Request $request)
    {
        $year = (int) $request->validate(['year' => ['required', 'integer']])['year'];

        AgendaEvent::query()->where('archived_year', $year)->update(['is_archived' => false, 'archived_year' => null]);
        MonthlyActivity::query()->where('archived_year', $year)->update(['is_archived' => false, 'archived_year' => null]);

        return back()->with('status', "Restored archived records for {$year}");
    }
}
