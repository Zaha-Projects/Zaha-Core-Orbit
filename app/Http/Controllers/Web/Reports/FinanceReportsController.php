<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DonationCash;
use App\Models\ZahaTimeBooking;
use Illuminate\Http\Request;

class FinanceReportsController extends Controller
{
    public function index()
    {
        $donations = DonationCash::orderByDesc('date')->get();
        $bookings = Booking::orderByDesc('booking_date')->get();
        $zahaTimeBookings = ZahaTimeBooking::orderByDesc('booking_date')->get();

        return view('pages.reports.finance', compact('donations', 'bookings', 'zahaTimeBookings'));
    }

    public function export(Request $request)
    {
        return redirect()
            ->back()
            ->with('status', __('app.roles.reports.exported'));
    }
}
