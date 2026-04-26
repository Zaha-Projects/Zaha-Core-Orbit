<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
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

        $calendarEvents = AgendaEvent::query()
            ->notArchived()
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('event_date')
            ->get()
            ->map(function (AgendaEvent $event): array {
                $resolvedDate = $event->event_date
                    ? Carbon::parse($event->event_date)->toDateString()
                    : Carbon::create(now()->year, max(1, (int) $event->month), max(1, (int) $event->day))->toDateString();

                return [
                    'title' => $event->event_name,
                    'start' => $resolvedDate,
                    'allDay' => true,
                ];
            })
            ->values();

        return view('dashboard', compact('cards', 'calendarEvents'));
    }
}
