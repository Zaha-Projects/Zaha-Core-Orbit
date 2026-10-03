<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\Ramadan\ReviewRamadanMonitoringReportRequest;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use Illuminate\Http\Request;

class RamadanMonitoringReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeReviewer($user);
        $query = MonitoringReport::query()
            ->where('subject_type', EventSubjectTypes::RAMADAN_IFTAR)
            ->where('status', MonitoringReport::STATUS_SUBMITTED)
            ->whereHas('ramadanIftar', function ($iftarQuery) use ($user) {
                if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
                    $iftarQuery->whereIn('branch_id', $user->scopedBranchIds());
                }
            })
            ->with(['ramadanIftar.branch', 'monitoringMethod', 'monitor'])
            ->latest('submitted_at');
        if (! $user->hasRole('super_admin')) {
            $query->where('monitor_user_id', '!=', $user->id);
        }

        return view('pages.events.ramadan.monitoring.reviews.index', ['reports' => $query->paginate(15)->withQueryString()]);
    }

    public function show(Request $request, MonitoringReport $monitoringReport)
    {
        $this->authorizeReport($request, $monitoringReport);
        $monitoringReport->load(['ramadanIftar.branch', 'monitoringMethod', 'monitor', 'verifications.verifier']);

        return view('pages.events.ramadan.monitoring.reviews.show', compact('monitoringReport'));
    }

    public function decide(ReviewRamadanMonitoringReportRequest $request, MonitoringReport $monitoringReport, RamadanIftarMonitoringService $service)
    {
        $iftar = $this->authorizeReport($request, $monitoringReport);
        $service->review($iftar, $monitoringReport, $request->user(), $request->input('decision'), $request->input('comment'));

        return redirect()->route('events.ramadan.monitoring-reviews.index')->with('success', __('ramadan_iftars.messages.monitoring_reviewed'));
    }

    private function authorizeReport(Request $request, MonitoringReport $report): RamadanIftar
    {
        $user = $request->user();
        $this->authorizeReviewer($user);
        abort_unless($report->subject_type === EventSubjectTypes::RAMADAN_IFTAR, 404);
        $iftar = $report->ramadanIftar;
        abort_unless($iftar, 404);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);

        return $iftar;
    }

    private function authorizeReviewer($user): void
    {
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor.review')), 403);
    }
}
