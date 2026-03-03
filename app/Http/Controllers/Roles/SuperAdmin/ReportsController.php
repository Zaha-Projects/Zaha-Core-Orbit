<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Center;
use App\Models\DonationCash;
use App\Models\MaintenanceRequest;
use App\Models\MonthlyActivity;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\EnterpriseAnalyticsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request, EnterpriseAnalyticsService $analyticsService)
    {
        $overview = [
            'branches' => Branch::count(),
            'centers' => Center::count(),
            'users' => User::count(),
            'vehicles' => Vehicle::count(),
        ];

        $operations = [
            'agenda_events' => AgendaEvent::count(),
            'monthly_activities' => MonthlyActivity::count(),
            'bookings' => Booking::count(),
            'maintenance_requests' => MaintenanceRequest::count(),
            'trips' => Trip::count(),
        ];

        $financials = [
            'payments' => Payment::count(),
            'payments_total' => Payment::sum('amount'),
            'donations' => DonationCash::count(),
            'donations_total' => DonationCash::sum('amount'),
        ];

        $maintenanceStatus = MaintenanceRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $agendaApprovals = AgendaApproval::query()
            ->selectRaw('decision, COUNT(*) as total')
            ->groupBy('decision')
            ->orderByDesc('total')
            ->get();

        $bookingStatus = Booking::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $year = (int) $request->input('year', now()->year);
        $analytics = $analyticsService->build($year);
        $branchMetrics = $analyticsService->branchMetrics($year);
        $years = range(now()->year - 2, now()->year + 1);
        $enterpriseFilters = $request->only(['year']);

        return view('roles.super_admin.reports', compact(
            'overview',
            'operations',
            'financials',
            'maintenanceStatus',
            'agendaApprovals',
            'bookingStatus',
            'analytics',
            'branchMetrics',
            'year',
            'years',
            'enterpriseFilters'
        ));
    }
}
