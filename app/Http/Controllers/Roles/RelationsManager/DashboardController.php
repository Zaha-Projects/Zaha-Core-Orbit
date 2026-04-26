<?php

namespace App\Http\Controllers\Roles\RelationsManager;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;

class DashboardController extends Controller
{
    public function index()
    {
        $year = now()->year;
        $agendaYearOverview = AgendaEvent::query()
            ->with(['department:id,name', 'eventCategory:id,name'])
            ->where(function ($query) use ($year) {
                $query->whereYear('event_date', $year)
                    ->orWhereNull('event_date');
            })
            ->where('status', '!=', 'draft')
            ->notArchived()
            ->orderByRaw('COALESCE(event_date, MAKEDATE(?, 1) + INTERVAL (month - 1) MONTH + INTERVAL (day - 1) DAY)', [$year])
            ->orderBy('month')
            ->orderBy('day')
            ->get()
            ->groupBy(fn ($event) => (int) (optional($event->event_date)->format('n') ?: $event->month));

        return view('roles.relations_manager.dashboard', compact('agendaYearOverview', 'year'));
    }
}
