<?php

namespace App\Http\Controllers;

use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService)
    {
        $user = $request->user();

        $cards = collect([
            ['permission' => 'agenda.view', 'title' => __('app.roles.relations.agenda.title'), 'description' => __('app.roles.relations.agenda.subtitle'), 'route' => 'role.relations.agenda.index', 'icon' => 'feather-calendar'],
            ['permission' => 'agenda.approve', 'workflow_module' => 'agenda', 'title' => __('app.roles.relations.approvals.title'), 'description' => __('app.roles.relations.approvals.subtitle'), 'route' => 'role.relations.approvals.index', 'icon' => 'feather-check-circle'],
            ['permission' => 'monthly_activities.view', 'title' => __('app.roles.programs.monthly_activities.title'), 'description' => __('app.roles.programs.monthly_activities.subtitle'), 'route' => 'role.relations.activities.index', 'icon' => 'feather-layers'],
            ['permission' => 'monthly_activities.approve', 'workflow_module' => 'monthly_activities', 'title' => __('app.roles.programs.monthly_activities.approvals.title'), 'description' => __('app.roles.programs.monthly_activities.approvals.subtitle'), 'route' => 'role.programs.approvals.index', 'icon' => 'feather-check-square'],
            ['permission' => 'reports.view', 'title' => __('app.roles.reports.title'), 'description' => __('app.roles.reports.subtitle'), 'route' => 'role.reports.index', 'icon' => 'feather-bar-chart-2'],
            ['permission' => 'users.view', 'title' => __('app.roles.super_admin.users.title'), 'description' => __('app.roles.super_admin.users.subtitle'), 'route' => 'role.super_admin.users', 'icon' => 'feather-users'],
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

        return view('dashboard', compact('cards'));
    }
}
