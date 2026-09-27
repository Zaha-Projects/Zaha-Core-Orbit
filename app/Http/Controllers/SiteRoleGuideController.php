<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class SiteRoleGuideController extends Controller
{
    public function __invoke(Request $request)
    {
        $guide = collect(config('site_role_guide', []));
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->get()->keyBy('name');

        $technical = $this->technicalReference();
        $guide = $guide->map(function (array $item, string $key) use ($roles, $technical): array {
            $role = $roles->get($key);
            $item['key'] = $key;
            $item['permissions'] = $role?->permissions->pluck('name')->sort()->values()->all() ?? [];
            $item['route_groups'] = $technical[$key]['routes'] ?? ['dashboard', 'profile'];
            $item['controllers'] = $technical[$key]['controllers'] ?? ['DashboardController'];

            return $item;
        });

        return view('pages.help.site-role-guide', [
            'guide' => $guide,
            'showTechnicalReference' => $request->user()->hasRole('super_admin'),
        ]);
    }

    private function technicalReference(): array
    {
        $relations = ['routes' => ['role.relations.*', 'events.ramadan.*'], 'controllers' => ['Agenda controllers', 'MonthlyActivities controllers', 'Ramadan controllers']];
        $followup = ['routes' => ['followup.*', 'evaluations.*', 'events.ramadan.iftars.monitoring.*'], 'controllers' => ['FollowupWorkspaceController', 'ActivityEvaluationsController', 'RamadanIftarMonitoringController']];
        $transport = ['routes' => ['role.transport.*'], 'controllers' => ['Transport controllers', 'TripSegmentsController', 'TripRoundsController']];

        return [
            'super_admin' => ['routes' => ['role.super_admin.*', 'events.ramadan.admin.*', 'evaluation.*'], 'controllers' => ['Access controllers', 'Admin Ramadan controllers', 'ReportsController']],
            'executive_manager' => $relations, 'programs_manager' => ['routes' => ['role.programs_manager.*', 'role.relations.activities.*'], 'controllers' => ['ProgramsManager DashboardController', 'MonthlyActivities controllers']],
            'relations_manager' => $relations, 'supervisor' => $relations, 'relations_officer' => $relations,
            'followup_officer' => $followup, 'evaluation_officer' => $followup, 'evaluation_followup_viewer' => $followup,
            'workshops_secretary' => $relations, 'branch_coordinator' => $relations,
            'volunteer_coordinator' => ['routes' => ['role.relations.activities.*'], 'controllers' => ['MonthlyActivities controllers']],
            'administrative_unit_manager' => ['routes' => ['role.relations.activities.*'], 'controllers' => ['MonthlyActivities controllers']],
            'communication_head' => ['routes' => ['role.programs.communications_requests.*'], 'controllers' => ['CommunicationsRequestsController']],
            'finance_officer' => ['routes' => ['role.finance.*'], 'controllers' => ['Finance controllers']],
            'maintenance_officer' => ['routes' => ['role.maintenance.*'], 'controllers' => ['Maintenance controllers']],
            'transport_officer' => $transport, 'movement_manager' => $transport, 'movement_editor' => $transport, 'movement_viewer' => $transport,
            'reports_viewer' => ['routes' => ['role.reports.*', 'role.enterprise.*'], 'controllers' => ['Reports controllers', 'EnterpriseDashboardController']],
            'staff' => ['routes' => ['role.staff.*'], 'controllers' => ['StaffAgendaController', 'StaffMonthlyActivitiesController']],
        ];
    }
}
