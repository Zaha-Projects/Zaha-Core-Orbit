<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CommunicationsRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationsRequestsController extends Controller
{
    private const DECISION_STATUSES = ['pending', 'changes_requested', 'rejected'];
    private const CONFIRMED_STATUSES = ['approved', 'preparing', 'ready', 'in_progress', 'completed', 'closed'];

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $requests = CommunicationsRequest::query()
            ->with(['event.branch'])
            ->whereHas('event')
            ->whereIn('status', self::DECISION_STATUSES)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusLabels = $this->statusLabels();
        $requirementLabels = $this->requirementLabels();
        $decisionCounts = $this->baseQuery()->whereIn('status', self::DECISION_STATUSES)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('pages.monthly_activities.communications.index', compact('requests', 'status', 'statusLabels', 'requirementLabels', 'decisionCounts'));
    }

    public function board(Request $request)
    {
        $filters = [
            'branch_id' => filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null,
            'month' => filter_var($request->input('month', now()->month), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ?: (int) now()->month,
            'year' => filter_var($request->input('year', now()->year), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2100]]) ?: (int) now()->year,
            'status' => $request->input('status', 'all'),
        ];

        $this->syncOperationalStatuses();

        $query = $this->baseQuery()
            ->whereIn('status', self::CONFIRMED_STATUSES)
            ->whereHas('event', function ($eventQuery) use ($filters): void {
                $eventQuery
                    ->whereYear('proposed_date', $filters['year'])
                    ->whereMonth('proposed_date', $filters['month'])
                    ->when($filters['branch_id'], fn ($branchQuery) => $branchQuery->where('branch_id', $filters['branch_id']));
            })
            ->when($filters['status'] !== 'all', fn ($statusQuery) => $statusQuery->where('status', $filters['status']));

        $requests = $query->get()->map(fn (CommunicationsRequest $request) => $this->presentRequest($request));
        $columns = collect(['approved', 'preparing', 'ready', 'in_progress', 'completed', 'closed'])
            ->mapWithKeys(fn ($status) => [$status => $requests->where('status', $status)->values()]);
        $calendarItems = $requests->groupBy('date_key');
        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);
        $statusLabels = $this->statusLabels();
        $requirementLabels = $this->requirementLabels();
        $monthStart = Carbon::create($filters['year'], $filters['month'], 1);
        $daysInMonth = $monthStart->daysInMonth;

        return view('pages.monthly_activities.communications.board', compact('requests', 'columns', 'calendarItems', 'branches', 'filters', 'statusLabels', 'requirementLabels', 'monthStart', 'daysInMonth'));
    }

    public function update(Request $request, CommunicationsRequest $communicationsRequest)
    {
        $data = $request->validate([
            'decision' => ['required_without:status', 'nullable', Rule::in(['approved', 'rejected', 'changes_requested'])],
            'status' => ['required_without:decision', 'nullable', Rule::in(['preparing', 'ready'])],
            'notes' => ['nullable', 'string', 'required_if:decision,rejected,changes_requested'],
            'media_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = $communicationsRequest->media_files ?? [];
        foreach ($request->file('media_files', []) as $file) {
            $files[] = $file->store("events/{$communicationsRequest->event_id}", 'public');
        }

        $newStatus = $data['decision'] ?? $data['status'];
        $communicationsRequest->update([
            'status' => $newStatus,
            'notes' => $data['notes'] ?? $communicationsRequest->notes,
            'media_files' => $files,
        ]);

        return back()->with('status', 'تم تحديث طلب قسم الاتصال.');
    }


    protected function syncOperationalStatuses(): void
    {
        $this->baseQuery()
            ->whereIn('status', ['approved', 'preparing', 'ready', 'in_progress', 'completed'])
            ->get()
            ->each(function (CommunicationsRequest $request): void {
                $event = $request->event;
                if (! $event) {
                    return;
                }

                $newStatus = null;
                if (in_array((string) $event->status, ['closed', 'completed'], true)) {
                    $newStatus = 'closed';
                } elseif ($event->proposed_date && $event->proposed_date->isPast() && ! $event->proposed_date->isToday()) {
                    $newStatus = 'completed';
                } elseif ($event->proposed_date && $event->proposed_date->isToday() && in_array($request->status, ['approved', 'preparing', 'ready'], true)) {
                    $newStatus = 'in_progress';
                }

                if ($newStatus && $request->status !== $newStatus) {
                    $request->update(['status' => $newStatus]);
                }
            });
    }

    protected function baseQuery()
    {
        return CommunicationsRequest::query()->with(['event.branch'])->whereHas('event');
    }

    protected function presentRequest(CommunicationsRequest $request): array
    {
        $event = $request->event;

        return [
            'model' => $request,
            'id' => $request->id,
            'status' => $request->status,
            'title' => $event?->title ?? '#'.$request->event_id,
            'branch' => $event?->branch?->name ?? '-',
            'date' => optional($event?->proposed_date)->format('Y-m-d'),
            'date_key' => optional($event?->proposed_date)->format('Y-m-d') ?: 'without-date',
            'time' => trim(($event?->time_from ?: '').($event?->time_to ? ' - '.$event->time_to : '')) ?: '-',
            'requirements' => $this->requirementsFor($event),
            'details' => $event?->media_coverage_notes ?: $request->notes,
            'url' => $event ? route('role.relations.activities.show', $event) : '#',
        ];
    }

    public function requirementsFor($event): array
    {
        if (! $event) {
            return [];
        }

        $labels = $this->requirementLabels();
        $requirements = [];
        if ($event->needs_media_coverage) {
            $requirements[] = $labels['media_coverage'];
        }
        if (data_get($event->execution_needs_payload, 'needs_invitations')) {
            $requirements[] = $labels['invitations'];
        }
        if ($event->requires_communications && $requirements === []) {
            $requirements[] = 'مطلوب من قسم الاتصال';
        }

        return array_values(array_unique($requirements));
    }

    protected function requirementLabels(): array
    {
        return [
            'media_coverage' => 'مطلوب تصوير / تغطية إعلامية',
            'invitations' => 'مطلوب بطاقات دعوة',
        ];
    }

    protected function statusLabels(): array
    {
        return [
            'pending' => 'بانتظار قرار القسم',
            'changes_requested' => 'يحتاج تعديل',
            'rejected' => 'مرفوض',
            'approved' => 'معتمد / مؤكد',
            'preparing' => 'جاري التحضير',
            'ready' => 'تم التجهيز',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'منتهية',
            'closed' => 'مغلقة',
        ];
    }
}
