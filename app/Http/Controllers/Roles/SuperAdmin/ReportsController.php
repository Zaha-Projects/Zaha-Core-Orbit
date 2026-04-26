<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\DonationCash;
use App\Models\MaintenanceRequest;
use App\Models\MonthlyActivity;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ZahaTimeOption;
use App\Services\EnterpriseAnalyticsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request, EnterpriseAnalyticsService $analyticsService)
    {
        $overview = [
            'branches' => Branch::count(),
            'centers' => 0,
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

        $activitiesWithNeedsPayload = MonthlyActivity::query()
            ->whereNotNull('execution_needs_payload')
            ->get(['id', 'execution_needs_payload']);
        $activitiesWithNeedsFollowup = MonthlyActivity::query()
            ->whereNotNull('execution_needs_followup')
            ->get(['id', 'execution_needs_followup']);

        $securedCount = 0;
        $notSecuredCount = 0;
        $scores = [];
        foreach ($activitiesWithNeedsFollowup as $activity) {
            foreach ((array) $activity->execution_needs_followup as $row) {
                if (($row['status'] ?? null) === 'secured') {
                    $securedCount++;
                } elseif (($row['status'] ?? null) === 'not_secured') {
                    $notSecuredCount++;
                }

                if (isset($row['effectiveness_score']) && $row['effectiveness_score'] !== null && $row['effectiveness_score'] !== '') {
                    $scores[] = (float) $row['effectiveness_score'];
                }
            }
        }

        $zahaUsage = [];
        foreach ($activitiesWithNeedsPayload as $activity) {
            $options = data_get($activity->execution_needs_payload, 'programs.zaha_time_options', []);
            foreach ((array) $options as $optionCode) {
                if (! filled($optionCode)) {
                    continue;
                }
                $zahaUsage[$optionCode] = ($zahaUsage[$optionCode] ?? 0) + 1;
            }
        }

        $zahaLookup = ZahaTimeOption::query()->orderBy('sort_order')->orderBy('name')->get();
        $zahaTimeStats = [
            'total' => $zahaLookup->count(),
            'active' => $zahaLookup->where('is_active', true)->count(),
            'usage' => $zahaLookup->map(function (ZahaTimeOption $option) use ($zahaUsage) {
                return [
                    'code' => $option->code,
                    'name' => $option->name,
                    'used' => (int) ($zahaUsage[$option->code] ?? 0),
                ];
            }),
        ];

        $executionNeedsStats = [
            'with_payload' => $activitiesWithNeedsPayload->count(),
            'with_followup' => $activitiesWithNeedsFollowup->count(),
            'secured_count' => $securedCount,
            'not_secured_count' => $notSecuredCount,
            'avg_effectiveness' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null,
        ];

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
            'executionNeedsStats',
            'zahaTimeStats',
            'analytics',
            'branchMetrics',
            'year',
            'years',
            'enterpriseFilters'
        ));
    }
}
