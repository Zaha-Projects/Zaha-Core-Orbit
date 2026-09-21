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
        $ramadanIftar->load(['monitoringReports.monitoringMethod', 'monitoringReports.monitor', 'meals', 'gifts', 'programSegments', 'executionTeams', 'volunteerRequirements', 'supplies', 'executionNeeds.executionNeedType']);

        return view('pages.events.ramadan.monitoring.index', [
            'ramadanIftar' => $ramadanIftar,
            'monitoringMethods' => MonitoringMethod::query()->active()->ordered()->get(),
            'candidates' => $monitoring->candidates($ramadanIftar),
            'monitoringWritable' => in_array($ramadanIftar->execution_status, [RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED], true) && $ramadanIftar->closed_at === null,
        ]);
    }

    public function store(StoreRamadanMonitoringReportRequest $request, RamadanIftar $ramadanIftar, RamadanIftarMonitoringService $monitoring)
    {
        $report = $monitoring->save($ramadanIftar, new MonitoringReport(), $request->validated(), $request->user());

        return redirect()->route('events.ramadan.iftars.monitoring.edit', [$ramadanIftar, $report])->with('success', __('ramadan_iftars.messages.monitoring_created'));
    }

    public function edit(Request $request, RamadanIftar $ramadanIftar, MonitoringReport $monitoringReport, RamadanIftarMonitoringService $monitoring)
    {
        $this->authorizeMonitoring($request, $ramadanIftar, false);
        abort_unless($ramadanIftar->monitoringReports()->whereKey($monitoringReport->id)->exists(), 404);
        $ramadanIftar->load(['meals', 'gifts', 'programSegments', 'executionTeams', 'volunteerRequirements', 'supplies', 'executionNeeds.executionNeedType']);
        $monitoringReport->load('verifications');

        return view('pages.events.ramadan.monitoring.edit', [
            'ramadanIftar' => $ramadanIftar, 'monitoringReport' => $monitoringReport,
            'monitoringMethods' => MonitoringMethod::query()->active()->ordered()->get(),
            'candidates' => $monitoring->candidates($ramadanIftar),
            'monitoringWritable' => in_array($ramadanIftar->execution_status, [RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED], true) && $ramadanIftar->closed_at === null,
        ]);
    }

    public function update(StoreRamadanMonitoringReportRequest $request, RamadanIftar $ramadanIftar, MonitoringReport $monitoringReport, RamadanIftarMonitoringService $monitoring)
    {
        $monitoring->save($ramadanIftar, $monitoringReport, $request->validated(), $request->user());

        return back()->with('success', __('ramadan_iftars.messages.monitoring_updated'));
    }

    public function submit(Request $request, RamadanIftar $ramadanIftar, MonitoringReport $monitoringReport, RamadanIftarMonitoringService $monitoring)
    {
        $this->authorizeMonitoring($request, $ramadanIftar);
        $monitoring->submit($ramadanIftar, $monitoringReport, $request->user());

        return redirect()->route('events.ramadan.iftars.show', $ramadanIftar)->with('success', __('ramadan_iftars.messages.monitoring_submitted'));
    }

    private function authorizeMonitoring(Request $request, RamadanIftar $iftar, bool $write = true): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor')), 403);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
        $allowedStatuses = [RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED];
        abort_unless($iftar->status === RamadanIftar::STATUS_APPROVED && in_array($iftar->execution_status, $allowedStatuses, true), 403);
        if ($write) {
            abort_if($iftar->closed_at !== null, 403);
        }
    }
}
