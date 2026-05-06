<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\DepartmentUnit;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService)
    {
        $user = $request->user();

        $cards = collect([
            ['permission' => 'agenda.view', 'title' => __('app.roles.relations.agenda.title'), 'description' => __('app.roles.relations.agenda.subtitle'), 'route' => 'role.relations.agenda.index', 'icon' => 'fas fa-calendar-days'],
            ['permission' => 'agenda.approve', 'workflow_module' => 'agenda', 'title' => __('app.roles.relations.approvals.title'), 'description' => __('app.roles.relations.approvals.subtitle'), 'route' => 'role.relations.approvals.index', 'icon' => 'fas fa-circle-check'],
            ['permission' => 'monthly_activities.view', 'title' => __('app.roles.programs.monthly_activities.title'), 'description' => __('app.roles.programs.monthly_activities.subtitle'), 'route' => 'role.relations.activities.index', 'icon' => 'fas fa-layer-group'],
            ['permission' => 'monthly_activities.approve', 'workflow_module' => 'monthly_activities', 'title' => __('app.roles.programs.monthly_activities.approvals.title'), 'description' => __('app.roles.programs.monthly_activities.approvals.subtitle'), 'route' => 'role.programs.approvals.index', 'icon' => 'fas fa-square-check'],
            ['permission' => 'reports.view', 'title' => __('app.roles.reports.title'), 'description' => __('app.roles.reports.subtitle'), 'route' => 'role.reports.index', 'icon' => 'fas fa-chart-simple'],
            ['permission' => 'users.view', 'title' => __('app.roles.super_admin.users.title'), 'description' => __('app.roles.super_admin.users.subtitle'), 'route' => 'role.super_admin.users', 'icon' => 'fas fa-users'],
        ])->filter(function (array $card) use ($dynamicWorkflowService, $user) {
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
                $participant = $event->participations->first(fn ($row) => (string) $row->participation_status === 'participant');
                $participantName = '—';

                if ($participant) {
                    $participantName = $participant->entity_type === 'branch'
                        ? $branchesById->get((int) $participant->entity_id, '—')
                        : $unitsById->get((int) $participant->entity_id, '—');
                }

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
                        'participant_entity' => $participantName,
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

                return [
                    'title' => $activity->title,
                    'start' => $resolvedDate,
                    'allDay' => true,
                    'type' => 'monthly_plan',
                    'color' => '#0e9f6e',
                    'extendedProps' => [
                        'owner_branch' => $branchesById->get((int) $activity->branch_id, '—'),
                        'participant_entity' => '—',
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

        return view('dashboard', compact('cards', 'calendarEvents', 'dashboardCalendarStats'));
    }
}
