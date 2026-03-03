<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\MonthlyActivity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnterpriseReportsController extends Controller
{
    protected function csvResponse(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportAgenda(Request $request): StreamedResponse
    {
        $events = AgendaEvent::query()->enterpriseFilter($request->all())->with(['department', 'eventCategory'])->orderBy('event_date')->get();

        return $this->csvResponse('agenda-report.csv', ['Date', 'Event', 'Department', 'Category', 'Status'], $events->map(fn ($e) => [
            optional($e->event_date)->format('Y-m-d'), $e->event_name, optional($e->department)->name, optional($e->eventCategory)->name, $e->status,
        ]));
    }

    public function exportMonthlyActivities(Request $request): StreamedResponse
    {
        $activities = MonthlyActivity::query()->enterpriseFilter($request->all())->with('branch')->orderBy('proposed_date')->get();

        return $this->csvResponse('monthly-activities-report.csv', ['Proposed Date', 'Title', 'Branch', 'Status', 'Executive Approval'], $activities->map(fn ($a) => [
            optional($a->proposed_date)->format('Y-m-d'), $a->title, optional($a->branch)->name, $a->status, $a->executive_approval_status,
        ]));
    }

    public function exportApprovalReport(Request $request): StreamedResponse
    {
        $activities = MonthlyActivity::query()->enterpriseFilter($request->all())->orderBy('proposed_date')->get();

        return $this->csvResponse('approval-report.csv', ['Title', 'Relations Officer', 'Relations Manager', 'Programs Officer', 'Programs Manager', 'Executive'], $activities->map(fn ($a) => [
            $a->title,
            $a->relations_officer_approval_status,
            $a->relations_manager_approval_status,
            $a->programs_officer_approval_status,
            $a->programs_manager_approval_status,
            $a->executive_approval_status,
        ]));
    }

    public function printable(Request $request)
    {
        $agenda = AgendaEvent::query()->enterpriseFilter($request->all())->orderBy('event_date')->get();
        $activities = MonthlyActivity::query()->enterpriseFilter($request->all())->orderBy('proposed_date')->get();

        return view('pages.reports.enterprise.printable', compact('agenda', 'activities'));
    }
}
