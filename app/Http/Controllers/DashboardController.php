<?php

namespace App\Http\Controllers;

use App\Modules\Events\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\CommunicationsRequest;
use App\Models\DepartmentUnit;
use App\Models\MonthlyActivity;
use App\Models\Setting;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanPeriod;
use Carbon\Carbon;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService)
    {
        $user = $request->user();
        if ($user?->hasRole('followup_officer')) {
            return redirect()->route('followup.dashboard');
        }
        if ($user?->hasRole('evaluation_officer')) {
            return redirect()->route('evaluations.dashboard');
        }
        $isProgramsManagerViewOnly = $user?->hasRole('programs_manager') && ! $user?->hasRole('super_admin');
        $isCommunicationHeadViewOnly = $user?->hasRole('communication_head') && ! $user?->hasRole('super_admin');

        if ($isCommunicationHeadViewOnly) {
            $communicationCounts = CommunicationsRequest::query()
                ->whereHas('event')
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $cards = collect([
                [
                    'title' => 'قرارات قسم الاتصال',
                    'description' => 'طلبات تحتاج قرار قبول أو طلب تعديل أو رفض من رئيس قسم الاتصال.',
                    'route' => 'role.programs.communications_requests.index',
                    'params' => ['status' => 'pending'],
                    'icon' => 'fas fa-camera-retro',
                    'value' => (int) $communicationCounts->get('pending', 0),
                ],
                [
                    'title' => 'متابعة الاتصال',
                    'description' => 'لوحة Kanban وتقويم للطلبات المعتمدة ومراحل التحضير والتنفيذ.',
                    'route' => 'role.programs.communications_requests.board',
                    'icon' => 'fas fa-table-columns',
                    'value' => (int) collect(['approved', 'preparing', 'ready', 'in_progress'])->sum(fn (string $status) => $communicationCounts->get($status, 0)),
                ],
                [
                    'title' => 'قيد التحضير',
                    'description' => 'طلبات مؤكدة تحتاج تجهيزات إعلامية أو بطاقات دعوة قبل التنفيذ.',
                    'route' => 'role.programs.communications_requests.board',
                    'params' => ['status' => 'preparing'],
                    'icon' => 'fas fa-wand-magic-sparkles',
                    'value' => (int) $communicationCounts->get('preparing', 0),
                ],
                [
                    'title' => 'جاهزة أو قيد التنفيذ',
                    'description' => 'طلبات تم تجهيزها أو يجري تنفيذها اليوم وتحتاج متابعة ميدانية.',
                    'route' => 'role.programs.communications_requests.board',
                    'params' => ['status' => 'all'],
                    'icon' => 'fas fa-bullhorn',
                    'value' => (int) ($communicationCounts->get('ready', 0) + $communicationCounts->get('in_progress', 0)),
                ],
            ])->map(function (array $card) {
                $card['url'] = route($card['route'], $card['params'] ?? []);

                return $card;
            });
        } else {
            $cards = collect([
            ['permission' => 'agenda.view', 'title' => __('app.roles.relations.agenda.title'), 'description' => __('app.roles.relations.agenda.subtitle'), 'route' => 'role.relations.agenda.index', 'icon' => 'fas fa-calendar-days'],
            ['permission' => 'agenda.approve', 'workflow_module' => 'agenda', 'title' => __('app.roles.relations.approvals.title'), 'description' => __('app.roles.relations.approvals.subtitle'), 'route' => 'role.relations.approvals.index', 'icon' => 'fas fa-circle-check'],
            ['permission' => 'monthly_activities.view', 'title' => __('app.roles.programs.monthly_activities.title'), 'description' => __('app.roles.programs.monthly_activities.subtitle'), 'route' => 'role.relations.activities.index', 'icon' => 'fas fa-layer-group'],
            ['permission' => 'monthly_activities.approve', 'workflow_module' => 'monthly_activities', 'title' => __('app.roles.programs.monthly_activities.approvals.title'), 'description' => __('app.roles.programs.monthly_activities.approvals.subtitle'), 'route' => 'role.programs.approvals.index', 'icon' => 'fas fa-square-check'],
            ['permission' => 'reports.view', 'title' => __('app.roles.reports.title'), 'description' => __('app.roles.reports.subtitle'), 'route' => 'role.reports.index', 'icon' => 'fas fa-chart-simple'],
            ['permission' => 'users.view', 'title' => __('app.roles.super_admin.users.title'), 'description' => __('app.roles.super_admin.users.subtitle'), 'route' => 'role.super_admin.users', 'icon' => 'fas fa-users'],
        ])->filter(function (array $card) use ($dynamicWorkflowService, $user, $isProgramsManagerViewOnly) {
            if ($isProgramsManagerViewOnly && in_array($card['permission'], ['agenda.approve', 'monthly_activities.approve'], true)) {
                return false;
            }

            if ($user->hasRole('super_admin') || $user->can($card['permission'])) {
                return true;
            }

            if (! empty($card['workflow_module'])) {
                return $dynamicWorkflowService->userMayParticipateInWorkflow($card['workflow_module'], $user);
            }

            return false;
        })
            ->map(function (array $card) {
                $card['url'] = route($card['route']);

                return $card;
            })
            ->values();
        }

        $branchesById = Branch::query()->pluck('name', 'id');
        $unitsById = DepartmentUnit::query()->pluck('name', 'id');

        $agendaEvents = AgendaEvent::query()
            ->notArchived()
            ->with('participations')
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('event_date')
            ->get()
            ->map(function (AgendaEvent $event) use ($branchesById, $unitsById): array {
                $resolvedDate = $event->event_date
                    ? Carbon::parse($event->event_date)->toDateString()
                    : Carbon::create(now()->year, max(1, (int) $event->month), max(1, (int) $event->day))->toDateString();
                $ownerParticipation = $event->participations->first(fn ($row) => (string) $row->participation_status === 'owner');
                $participantEntities = $event->participations
                    ->filter(fn ($row) => (string) $row->participation_status === 'participant')
                    ->map(function ($participant) use ($branchesById, $unitsById): string {
                        return $participant->entity_type === 'branch'
                            ? (string) $branchesById->get((int) $participant->entity_id, '—')
                            : (string) $unitsById->get((int) $participant->entity_id, '—');
                    })
                    ->filter(fn (string $name) => $name !== '—' && $name !== '')
                    ->unique()
                    ->values();

                return [
                    'title' => $event->event_name,
                    'start' => $resolvedDate,
                    'allDay' => true,
                    'type' => 'agenda',
                    'color' => '#1d4ed8',
                    'extendedProps' => [
                        'owner_branch' => $ownerParticipation && $ownerParticipation->entity_type === 'branch'
                            ? $branchesById->get((int) $ownerParticipation->entity_id, '—')
                            : '—',
                        'participant_entity' => $participantEntities->implode('، '),
                        'participant_entities' => $participantEntities->all(),
                        'participant_entities_count' => $participantEntities->count(),
                    ],
                ];
            })
            ->values();

        $monthlyActivityEvents = MonthlyActivity::query()
            ->where('is_archived', false)
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('activity_date')
            ->get()
            ->map(function (MonthlyActivity $activity) use ($branchesById): array {
                $resolvedDate = $activity->activity_date
                    ? Carbon::parse($activity->activity_date)->toDateString()
                    : Carbon::create(now()->year, max(1, (int) $activity->month), max(1, (int) $activity->day))->toDateString();
                $timeFrom = $activity->time_from ? Carbon::parse((string) $activity->time_from)->format('H:i') : null;
                $timeTo = $activity->time_to ? Carbon::parse((string) $activity->time_to)->format('H:i') : null;
                $resolvedStart = $timeFrom ? "{$resolvedDate}T{$timeFrom}:00" : $resolvedDate;
                $resolvedEnd = $timeTo ? "{$resolvedDate}T{$timeTo}:00" : null;
                $isTimedEvent = $timeFrom !== null;

                return [
                    'title' => $activity->title,
                    'start' => $resolvedStart,
                    'end' => $resolvedEnd,
                    'allDay' => ! $isTimedEvent,
                    'type' => 'monthly_plan',
                    'color' => '#0e9f6e',
                    'extendedProps' => [
                        'owner_branch' => $branchesById->get((int) $activity->branch_id, '—'),
                        'participant_entity' => '—',
                        'participant_entities' => [],
                        'participant_entities_count' => 0,
                        'time_from' => $timeFrom,
                        'time_to' => $timeTo,
                    ],
                ];
            })
            ->values();

        $calendarEvents = $agendaEvents->concat($monthlyActivityEvents)->values();

        $agendaCount = $agendaEvents->count();
        $monthlyCount = $monthlyActivityEvents->count();
        $totalCount = $calendarEvents->count();
        $topBranch = $calendarEvents
            ->map(fn (array $event) => (string) data_get($event, 'extendedProps.owner_branch', '—'))
            ->filter(fn (string $name) => $name !== '—' && $name !== '')
            ->countBy()
            ->sortDesc();

        $dashboardCalendarStats = [
            'total' => $totalCount,
            'agenda' => $agendaCount,
            'monthly' => $monthlyCount,
            'top_branch_name' => (string) ($topBranch->keys()->first() ?? '—'),
            'top_branch_count' => (int) ($topBranch->first() ?? 0),
        ];

        $ramadanDashboard = $this->configuredRamadanDashboard($user);

        return view('dashboard', compact('cards', 'calendarEvents', 'dashboardCalendarStats', 'ramadanDashboard'));
    }

    private function configuredRamadanDashboard($user): ?array
    {
        if (Setting::valueOf('ramadan_dashboard_enabled', '1') !== '1') {
            return null;
        }

        return $this->ramadanDashboard($user);
    }

    private function ramadanDashboard($user): ?array
    {
        $period = RamadanPeriod::current();
        if (! $period || ! $user || (! $user->hasRole('super_admin') && ! $user->can('ramadan_iftars.view'))) {
            return null;
        }

        $base = RamadanIftar::query()
            ->whereDoesntHave('versions')
            ->whereBetween('planned_date', [$period->start_date->toDateString(), $period->end_date->toDateString()]);
        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            $branchIds = $user->scopedBranchIds();
            $branchIds === [] ? $base->whereRaw('1 = 0') : $base->whereIn('branch_id', $branchIds);
        }

        $today = now()->toDateString();
        $metrics = (clone $base)->selectRaw(
            "COUNT(*) AS total,
            SUM(CASE WHEN planned_date > ? AND closed_at IS NULL THEN 1 ELSE 0 END) AS upcoming,
            SUM(CASE WHEN planned_date = ? THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS awaiting_approval,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN execution_status IN (?, ?) AND closed_at IS NULL THEN 1 ELSE 0 END) AS execution_active,
            SUM(CASE WHEN execution_status = ? AND closed_at IS NULL AND NOT EXISTS (
                SELECT 1 FROM monitoring_reports mr WHERE mr.subject_type = ? AND mr.subject_id = ramadan_iftars.id
            ) THEN 1 ELSE 0 END) AS awaiting_monitoring,
            SUM(CASE WHEN EXISTS (
                SELECT 1 FROM monitoring_reports mr WHERE mr.subject_type = ? AND mr.subject_id = ramadan_iftars.id AND mr.status = ?
            ) THEN 1 ELSE 0 END) AS monitoring_submitted,
            SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) AS closed",
            [
                $today, $today, RamadanIftar::STATUS_DRAFT, RamadanIftar::STATUS_SUBMITTED,
                RamadanIftar::STATUS_APPROVED, RamadanIftar::EXECUTION_STATUS_PLANNED,
                RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, RamadanIftar::EXECUTION_STATUS_COMPLETED,
                EventSubjectTypes::RAMADAN_IFTAR, EventSubjectTypes::RAMADAN_IFTAR,
                MonitoringReport::STATUS_SUBMITTED,
            ]
        )->first();

        $upcoming = (clone $base)->with(['branch:id,name', 'communityOrganization:id,name'])
            ->whereDate('planned_date', '>=', $today)
            ->whereNull('closed_at')
            ->orderBy('planned_date')->orderBy('time_from')->limit(5)->get();

        return [
            'period' => $period,
            'metrics' => collect($metrics?->getAttributes() ?? [])->map(fn ($value) => (int) $value)->all(),
            'upcoming' => $upcoming,
            'can_create' => $user->hasRole('super_admin') || $user->can('ramadan_iftars.create'),
            'can_approve' => $user->hasRole('super_admin') || $user->can('ramadan_iftars.approve'),
        ];
    }
}
