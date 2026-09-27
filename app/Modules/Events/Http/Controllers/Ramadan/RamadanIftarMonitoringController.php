<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\Ramadan\StoreRamadanMonitoringReportRequest;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use Illuminate\Http\Request;

class RamadanIftarMonitoringController extends Controller
{
    public function index(Request $request, RamadanIftar $ramadanIftar, RamadanIftarMonitoringService $monitoring)
    {
        $this->authorizeMonitoring($request, $ramadanIftar, false);
        $ramadanIftar->load(['monitoringReports.monitoringMethod', 'monitoringReports.monitor', 'monitoringReports.verifications', 'targetGroupSelections.targetGroup', 'targetGroupSelections.beneficiarySegment', 'attendees', 'meals', 'gifts', 'programSegments', 'executionTeams', 'volunteerRequirements.beneficiarySegment', 'supplies', 'executionNeeds.executionNeedType']);
        $monitoringReport = $ramadanIftar->monitoringReports
            ->whereIn('status', [MonitoringReport::STATUS_DRAFT, MonitoringReport::STATUS_RETURNED])
            ->sortByDesc('updated_at')->first();

        return view('pages.events.ramadan.monitoring.index', [
            'ramadanIftar' => $ramadanIftar,
            'monitoringMethods' => MonitoringMethod::query()->active()->ordered()->get(),
            'candidates' => $monitoring->candidates($ramadanIftar),
            'monitoringReport' => $monitoringReport,
            'monitoringWritable' => $ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_COMPLETED
                && $ramadanIftar->closed_at === null
                && ! $ramadanIftar->monitoringReports->contains(fn ($report) => in_array($report->status, [MonitoringReport::STATUS_SUBMITTED, MonitoringReport::STATUS_APPROVED], true)),
        ]);
    }

    public function store(StoreRamadanMonitoringReportRequest $request, RamadanIftar $ramadanIftar, RamadanIftarMonitoringService $monitoring)
    {
        $report = $monitoring->saveAndSubmit($ramadanIftar, new MonitoringReport(), $request->validated(), $request->user());

        return redirect()->route('events.ramadan.iftars.show', $ramadanIftar)->with('success', __('ramadan_iftars.messages.monitoring_submitted'));
    }

    public function update(StoreRamadanMonitoringReportRequest $request, RamadanIftar $ramadanIftar, MonitoringReport $monitoringReport, RamadanIftarMonitoringService $monitoring)
    {
        $monitoring->saveAndSubmit($ramadanIftar, $monitoringReport, $request->validated(), $request->user());

        return redirect()->route('events.ramadan.iftars.show', $ramadanIftar)->with('success', __('ramadan_iftars.messages.monitoring_submitted'));
    }

    private function authorizeMonitoring(Request $request, RamadanIftar $iftar, bool $write = true): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor')), 403);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
        abort_unless($iftar->status === RamadanIftar::STATUS_APPROVED && $iftar->execution_status === RamadanIftar::EXECUTION_STATUS_COMPLETED, 403);
        if ($write) {
            abort_if($iftar->closed_at !== null, 403);
        }
    }
}
