<?php

namespace App\Http\Controllers\Web\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\MonthlyActivity;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use App\Services\AgendaWorkflowPresenter;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function exportAgenda(Request $request, AgendaWorkflowPresenter $agendaWorkflowPresenter): StreamedResponse
    {
        $events = AgendaEvent::query()
            ->enterpriseFilter($request->all())
            ->with([
                'department',
                'eventCategory',
                'workflowInstance.workflow.steps.role',
                'workflowInstance.currentStep.role',
                'workflowInstance.logs.step.role',
                'workflowInstance.logs.actor',
            ])
            ->orderBy('event_date')
            ->get();

        return $this->csvResponse('agenda-report.csv', ['Date', 'Event', 'Department', 'Category', 'Status', 'Current Step', 'Current Assignee'], $events->map(function (AgendaEvent $event) use ($agendaWorkflowPresenter) {
            $summary = $agendaWorkflowPresenter->present($event);

            return [
                optional($event->event_date)->format('Y-m-d'),
                $event->event_name,
                optional($event->department)->name,
                optional($event->eventCategory)->name,
                $summary['status_label'],
                $summary['current_step_label'],
                $summary['current_role_label'],
            ];
        }));
    }

    public function exportMonthlyActivities(Request $request, DynamicWorkflowService $dynamicWorkflowService): StreamedResponse
    {
        $activities = MonthlyActivity::query()
            ->enterpriseFilter($request->all())
            ->with(['branch', 'workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission'])
            ->orderBy('proposed_date')
            ->get();

        return $this->csvResponse('monthly-activities-report.csv', ['Proposed Date', 'Title', 'Branch', 'Status', 'Current Step', 'Current Assignee'], $activities->map(function (MonthlyActivity $activity) use ($dynamicWorkflowService) {
            $summary = $this->monthlyWorkflowSummary($activity, $dynamicWorkflowService);

            return [
                optional($activity->proposed_date)->format('Y-m-d'),
                $activity->title,
                optional($activity->branch)->name,
                $summary['status_label'],
                $summary['current_step_label'],
                $summary['current_role_label'],
            ];
        }));
    }

    public function exportApprovalReport(Request $request, DynamicWorkflowService $dynamicWorkflowService): StreamedResponse
    {
        $activities = MonthlyActivity::query()
            ->enterpriseFilter($request->all())
            ->with(['workflowInstance.workflow.steps.role', 'workflowInstance.logs.step.role'])
            ->orderBy('proposed_date')
            ->get();

        $workflow = $dynamicWorkflowService->findActiveWorkflow('monthly_activities');
        $workflow?->loadMissing('steps.role');
        $steps = collect($workflow?->steps ?? []);

        $headers = ['Title', ...$steps->map(fn (WorkflowStep $step) => $step->name_en ?: $this->fallbackLabel($step->step_key))->all()];

        return $this->csvResponse('approval-report.csv', $headers, $activities->map(function (MonthlyActivity $activity) use ($steps) {
            $latestLogByStep = collect($activity->workflowInstance?->logs ?? [])
                ->groupBy('workflow_step_id')
                ->map(fn (Collection $logs): ?WorkflowLog => $logs->sortByDesc(fn (WorkflowLog $log) => $log->acted_at?->timestamp ?? 0)->first());

            return [
                $activity->title,
                ...$steps->map(function (WorkflowStep $step) use ($activity, $latestLogByStep) {
                    if ($step->condition_field && ! $this->stepAppliesToActivity($step, $activity)) {
                        return 'N/A';
                    }

                    $action = $latestLogByStep->get($step->id)?->action;

                    return $action ? $this->workflowActionLabel($action) : 'Pending';
                })->all(),
            ];
        }));
    }

    public function printable(Request $request, AgendaWorkflowPresenter $agendaWorkflowPresenter, DynamicWorkflowService $dynamicWorkflowService)
    {
        $agenda = AgendaEvent::query()
            ->enterpriseFilter($request->all())
            ->with([
                'workflowInstance.workflow.steps.role',
                'workflowInstance.currentStep.role',
                'workflowInstance.logs.step.role',
                'workflowInstance.logs.actor',
            ])
            ->orderBy('event_date')
            ->get()
            ->each(fn (AgendaEvent $event) => $event->setAttribute('workflow_summary', $agendaWorkflowPresenter->present($event)));

        $activities = MonthlyActivity::query()
            ->enterpriseFilter($request->all())
            ->with(['workflowInstance.currentStep.role', 'workflowInstance.currentStep.permission'])
            ->orderBy('proposed_date')
            ->get()
            ->each(function (MonthlyActivity $activity) use ($dynamicWorkflowService) {
                $activity->setAttribute('workflow_summary', $this->monthlyWorkflowSummary($activity, $dynamicWorkflowService));
            });

        return view('pages.reports.enterprise.printable', compact('agenda', 'activities'));
    }

    protected function monthlyWorkflowSummary(MonthlyActivity $activity, DynamicWorkflowService $dynamicWorkflowService): array
    {
        $instance = $activity->workflowInstance;
        $step = $instance ? $dynamicWorkflowService->currentStep($instance) : null;
        $roleLabel = $step?->role?->display_name
            ?: ($step?->permission?->name ?: __('app.common.na'));

        return [
            'status_label' => $this->workflowActionLabel((string) ($instance?->status ?: $activity->status ?: 'pending')),
            'current_step_label' => $step?->name_en ?: ($step?->name_ar ?: __('app.common.na')),
            'current_role_label' => $roleLabel,
        ];
    }

    protected function stepAppliesToActivity(WorkflowStep $step, MonthlyActivity $activity): bool
    {
        if (! filled($step->condition_field)) {
            return true;
        }

        return $this->normalizeComparableValue(data_get($activity, $step->condition_field))
            === $this->normalizeComparableValue($step->condition_value);
    }

    protected function normalizeComparableValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'true', 'yes', 'on' => '1',
            'false', 'no', 'off', '' => '0',
            default => $normalized,
        };
    }

    protected function workflowActionLabel(string $value): string
    {
        $translationKey = 'workflow_ui.approvals.status_labels.' . $value;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return $this->fallbackLabel($value);
    }

    protected function fallbackLabel(?string $value): string
    {
        if (! $value) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }
}
