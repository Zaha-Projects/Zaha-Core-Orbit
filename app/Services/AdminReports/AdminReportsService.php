<?php

namespace App\Services\AdminReports;

use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\DonationCash;
use App\Models\MaintenanceRequest;
use App\Models\MonthlyActivity;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkflowInstance;
use App\Models\ZahaTimeOption;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AdminReportsService
{
    public function build(int $year, int $month): array
    {
        $executionNeeds = $this->executionNeeds();
        $activitiesWithNeedsPayload = $executionNeeds['payload_rows'];
        unset($executionNeeds['payload_rows']);

        return [
            'overview' => $this->overview(),
            'operations' => $this->operations(),
            'financials' => $this->financials(),
            'statuses' => $this->statuses(),
            'execution_needs' => $executionNeeds,
            'zaha_time' => $this->zahaTime($activitiesWithNeedsPayload),
            'daily_operation_logs' => $this->dailyOperationLogs(),
            'user_delay_stats' => $this->userDelayStats(),
            'relations' => $this->cachedRelationsReport($year, $month),
        ];
    }

    public function cacheKey(int $year, int $month): string
    {
        return sprintf('%s.%d.%02d', $this->cachePrefix(), $year, $month);
    }

    public function forgetRelationsCache(int $year, int $month): void
    {
        Cache::forget($this->cacheKey($year, $month));
    }

    public function cacheConfig(): array
    {
        return [
            'enabled' => $this->cacheEnabled(),
            'ttl_minutes' => $this->cacheTtlMinutes(),
            'prefix' => $this->cachePrefix(),
            'enabled_key' => config('admin_reports.cache.enabled_key'),
            'ttl_key' => config('admin_reports.cache.ttl_key'),
            'prefix_key' => config('admin_reports.cache.prefix_key'),
        ];
    }

    protected function cachedRelationsReport(int $year, int $month): array
    {
        if (! $this->cacheEnabled()) {
            return $this->relationsReport($year, $month, null);
        }

        $cacheKey = $this->cacheKey($year, $month);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes()), fn () => $this->relationsReport($year, $month, $cacheKey));
    }

    protected function relationsReport(int $year, int $month, ?string $cacheKey): array
    {
        $branchActivityRows = MonthlyActivity::query()
            ->leftJoin('workflow_instances', function ($join): void {
                $join->on('workflow_instances.entity_id', '=', 'monthly_activities.id')
                    ->where('workflow_instances.entity_type', '=', MonthlyActivity::class);
            })
            ->leftJoin('workflow_steps', 'workflow_steps.id', '=', 'workflow_instances.current_step_id')
            ->select('monthly_activities.branch_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN monthly_activities.execution_status = 'executed' OR monthly_activities.status IN ('executed','completed','closed','post_execution_submitted') OR monthly_activities.lifecycle_status IN ('Executed','Evaluated','Closed','Exec Director Approved') OR workflow_instances.status = 'approved' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN workflow_instances.status IN ('pending','in_progress') THEN 1 ELSE 0 END) as pending_approval")
            ->selectRaw("SUM(CASE WHEN workflow_instances.status = 'changes_requested' THEN 1 ELSE 0 END) as pending_changes")
            ->selectRaw("SUM(CASE WHEN workflow_instances.status IN ('pending','in_progress') AND workflow_steps.step_key = 'monthly_branch_coordinator_review' THEN 1 ELSE 0 END) as pending_branch_coordinator")
            ->whereYear('monthly_activities.activity_date', $year)
            ->whereMonth('monthly_activities.activity_date', $month)
            ->groupBy('monthly_activities.branch_id')
            ->get()
            ->keyBy('branch_id');

        $pendingDeleteRequests = MonthlyPlanDeleteRequest::query()
            ->select('branch_id')
            ->selectRaw('COUNT(*) as total')
            ->where('status', 'pending')
            ->whereYear('requested_at', $year)
            ->whereMonth('requested_at', $month)
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $pendingEditRequests = MonthlyPlanEditRequest::query()
            ->select('branch_id')
            ->selectRaw('COUNT(*) as total')
            ->where('status', 'pending')
            ->whereYear('requested_at', $year)
            ->whereMonth('requested_at', $month)
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $monthlyByBranch = Branch::query()->orderBy('name')->get(['id', 'name'])
            ->map(function (Branch $branch) use ($branchActivityRows, $pendingDeleteRequests, $pendingEditRequests): array {
                $row = $branchActivityRows->get($branch->id);

                return [
                    'branch' => $branch->name,
                    'total' => (int) ($row->total ?? 0),
                    'completed' => (int) ($row->completed ?? 0),
                    'pending_approval' => (int) ($row->pending_approval ?? 0),
                    'pending_changes' => (int) ($row->pending_changes ?? 0),
                    'pending_delete' => (int) ($pendingDeleteRequests[$branch->id] ?? 0),
                    'pending_edit' => (int) ($pendingEditRequests[$branch->id] ?? 0),
                    'pending_branch_coordinator' => (int) ($row->pending_branch_coordinator ?? 0),
                ];
            });

        $agendaStatus = AgendaEvent::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereYear('event_date', $year)
            ->whereMonth('event_date', $month)
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $approvalSpeed = WorkflowInstance::query()
            ->whereIn('entity_type', [MonthlyActivity::class, AgendaEvent::class, MonthlyPlanEditRequest::class, MonthlyPlanDeleteRequest::class])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->whereYear('started_at', $year)
            ->whereMonth('started_at', $month)
            ->get(['entity_type', 'started_at', 'completed_at'])
            ->groupBy('entity_type')
            ->map(function ($rows, string $entityType): array {
                $minutes = $rows->map(fn ($row) => $row->started_at->diffInMinutes($row->completed_at))->values();

                return [
                    'module' => class_basename($entityType),
                    'total' => $minutes->count(),
                    'avg_hours' => round($minutes->avg() / 60, 2),
                    'min_hours' => round($minutes->min() / 60, 2),
                    'max_hours' => round($minutes->max() / 60, 2),
                ];
            })
            ->values();

        return [
            'period' => Carbon::create($year, $month, 1)->format('Y-m'),
            'cache_key' => $cacheKey ?? $this->cacheKey($year, $month),
            'cache_enabled' => $this->cacheEnabled(),
            'cache_ttl_minutes' => $this->cacheTtlMinutes(),
            'monthly_by_branch' => $monthlyByBranch,
            'agenda_status' => $agendaStatus,
            'approval_speed' => $approvalSpeed,
        ];
    }

    protected function overview(): array
    {
        return [
            'branches' => Branch::count(),
            'centers' => 0,
            'users' => User::count(),
            'vehicles' => Vehicle::count(),
        ];
    }

    protected function operations(): array
    {
        return [
            'agenda_events' => AgendaEvent::count(),
            'monthly_activities' => MonthlyActivity::count(),
            'bookings' => Booking::count(),
            'maintenance_requests' => MaintenanceRequest::count(),
            'trips' => Trip::count(),
        ];
    }

    protected function financials(): array
    {
        return [
            'payments' => Payment::count(),
            'payments_total' => Payment::sum('amount'),
            'donations' => DonationCash::count(),
            'donations_total' => DonationCash::sum('amount'),
        ];
    }

    protected function statuses(): array
    {
        return [
            'maintenance' => MaintenanceRequest::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get(),
            'agenda_approvals' => AgendaApproval::query()->selectRaw('decision, COUNT(*) as total')->groupBy('decision')->orderByDesc('total')->get(),
            'bookings' => Booking::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get(),
        ];
    }

    protected function executionNeeds(): array
    {
        $activitiesWithNeedsPayload = MonthlyActivity::query()->whereNotNull('execution_needs_payload')->get(['id', 'execution_needs_payload']);
        $activitiesWithNeedsFollowup = MonthlyActivity::query()->whereNotNull('execution_needs_followup')->get(['id', 'execution_needs_followup']);

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

        return [
            'with_payload' => $activitiesWithNeedsPayload->count(),
            'with_followup' => $activitiesWithNeedsFollowup->count(),
            'secured_count' => $securedCount,
            'not_secured_count' => $notSecuredCount,
            'avg_effectiveness' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null,
            'payload_rows' => $activitiesWithNeedsPayload,
        ];
    }

    protected function zahaTime($activitiesWithNeedsPayload): array
    {
        $zahaUsage = [];
        foreach ($activitiesWithNeedsPayload as $activity) {
            $options = data_get($activity->execution_needs_payload, 'programs.zaha_time_options', []);
            foreach ((array) $options as $optionCode) {
                if (filled($optionCode)) {
                    $zahaUsage[$optionCode] = ($zahaUsage[$optionCode] ?? 0) + 1;
                }
            }
        }

        $zahaLookup = ZahaTimeOption::query()->orderBy('sort_order')->orderBy('name')->get();

        return [
            'total' => $zahaLookup->count(),
            'active' => $zahaLookup->where('is_active', true)->count(),
            'usage' => $zahaLookup->map(fn (ZahaTimeOption $option) => [
                'code' => $option->code,
                'name' => $option->name,
                'used' => (int) ($zahaUsage[$option->code] ?? 0),
            ]),
        ];
    }

    protected function dailyOperationLogs()
    {
        return AuditLog::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();
    }

    protected function userDelayStats()
    {
        return AuditLog::query()
            ->selectRaw('user_id, MIN(created_at) as first_action_at, MAX(created_at) as last_action_at, COUNT(*) as total_actions')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_actions')
            ->limit(25)
            ->get();
    }

    protected function cacheEnabled(): bool
    {
        return filter_var(Setting::valueOf(config('admin_reports.cache.enabled_key'), config('admin_reports.cache.default_enabled') ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    protected function cacheTtlMinutes(): int
    {
        return max(1, (int) Setting::valueOf(config('admin_reports.cache.ttl_key'), (string) config('admin_reports.cache.default_ttl_minutes')));
    }

    protected function cachePrefix(): string
    {
        return trim((string) Setting::valueOf(config('admin_reports.cache.prefix_key'), config('admin_reports.cache.default_prefix'))) ?: config('admin_reports.cache.default_prefix');
    }
}
