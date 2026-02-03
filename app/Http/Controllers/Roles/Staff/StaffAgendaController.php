<?php

namespace App\Http\Controllers\Roles\Staff;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffAgendaController extends Controller
{
    public function index(Request $request)
    {
        $query = AgendaEvent::orderBy('month')->orderBy('day');

        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'));
            $query->where('month', $date->format('m'))
                ->where('day', $date->format('d'));
        }

        $events = $query->get();

        return view('roles.staff.agenda', compact('events'));
    }
}
