<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DonationCash;
use App\Models\ZahaTimeBooking;
use Illuminate\Http\Request;

class FinanceReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // Bookings carry a branch; branch-scoped users only see their own branches. Cash donations have no branch column.
        $scopeToBranches = fn ($query) => $query->when(
            $user->isBranchScopedPlanningUser(),
            fn ($scoped) => $scoped->whereIn('branch_id', $user->scopedBranchIds())
        );

        $donationsCount = DonationCash::query()->count();
        $bookingsCount = $scopeToBranches(Booking::query())->count();
        $zahaTimeBookingsCount = $scopeToBranches(ZahaTimeBooking::query())->count();

        return view('pages.reports.finance', compact('donationsCount', 'bookingsCount', 'zahaTimeBookingsCount'));
    }
}
