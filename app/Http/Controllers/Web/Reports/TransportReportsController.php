<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Trip;

class TransportReportsController extends Controller
{
    /**
     * Trips are organisation-level fleet records with no branch column, so no branch scope applies.
     */
    public function index()
    {
        $trips = Trip::orderByDesc('trip_date')->get();

        return view('pages.reports.transport', compact('trips'));
    }
}
