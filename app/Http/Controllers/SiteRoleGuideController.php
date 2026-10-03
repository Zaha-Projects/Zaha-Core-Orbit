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
        $presentation = $this->presentation();
        $guide = $guide->map(function (array $item, string $key) use ($roles, $technical, $presentation): array {
            $role = $roles->get($key);
            $item['key'] = $key;
            $item['permissions'] = $role?->permissions->pluck('name')->sort()->values()->all() ?? [];
            $item['route_groups'] = $technical[$key]['routes'] ?? ['dashboard', 'profile'];
            $item['controllers'] = $technical[$key]['controllers'] ?? ['DashboardController'];
            $item['presentation'] = $presentation[$key] ?? $presentation['default'];
            $item['capabilities'] = $this->capabilities($item['permissions']);

            return $item;
        });

        return view('pages.help.site-role-guide', [
            'guide' => $guide,
            'showTechnicalReference' => $request->user()->hasRole('super_admin'),
        ]);
    }

    private function capabilities(array $permissions): array
    {
        $matches = static fn (array $needles): bool => collect($permissions)->contains(
            fn (string $permission) => collect($needles)->contains(fn (string $needle) => str_contains($permission, $needle))
        );

        return [
            'create' => $matches(['.create']),
            'edit' => $matches(['.edit', '.update', '.manage']),
            'execute' => $matches(['.execute']),
            'review' => $matches(['.view', '.verify', '.monitor', 'evaluation.']),
            'approve' => $matches(['.approve', '.review']),
            'close' => $matches(['.close']),
        ];
    }

    private function presentation(): array
    {
        return [
            'super_admin' => ['icon' => 'fa-sliders', 'category' => 'administration', 'accent' => 'violet'],
            'executive_manager' => ['icon' => 'fa-building-shield', 'category' => 'leadership', 'accent' => 'indigo'],
            'programs_manager' => ['icon' => 'fa-diagram-project', 'category' => 'leadership', 'accent' => 'blue'],
            'relations_manager' => ['icon' => 'fa-people-arrows-left-right', 'category' => 'leadership', 'accent' => 'cyan'],
            'supervisor' => ['icon' => 'fa-stamp', 'category' => 'leadership', 'accent' => 'amber'],
            'relations_officer' => ['icon' => 'fa-comments', 'category' => 'operations', 'accent' => 'teal'],
            'followup_officer' => ['icon' => 'fa-list-check', 'category' => 'review', 'accent' => 'emerald'],
            'evaluation_officer' => ['icon' => 'fa-clipboard-check', 'category' => 'review', 'accent' => 'green'],
            'evaluation_followup_viewer' => ['icon' => 'fa-eye', 'category' => 'review', 'accent' => 'slate'],
            'workshops_secretary' => ['icon' => 'fa-calendar-check', 'category' => 'operations', 'accent' => 'rose'],
            'branch_coordinator' => ['icon' => 'fa-code-branch', 'category' => 'operations', 'accent' => 'sky'],
            'volunteer_coordinator' => ['icon' => 'fa-hand-holding-heart', 'category' => 'operations', 'accent' => 'pink'],
            'administrative_unit_manager' => ['icon' => 'fa-folder-tree', 'category' => 'operations', 'accent' => 'stone'],
            'communication_head' => ['icon' => 'fa-bullhorn', 'category' => 'support', 'accent' => 'orange'],
            'finance_officer' => ['icon' => 'fa-wallet', 'category' => 'support', 'accent' => 'emerald'],
            'maintenance_officer' => ['icon' => 'fa-screwdriver-wrench', 'category' => 'support', 'accent' => 'amber'],
            'transport_officer' => ['icon' => 'fa-truck-fast', 'category' => 'support', 'accent' => 'blue'],
            'reports_viewer' => ['icon' => 'fa-chart-column', 'category' => 'review', 'accent' => 'indigo'],
            'staff' => ['icon' => 'fa-user', 'category' => 'operations', 'accent' => 'slate'],
            'movement_manager' => ['icon' => 'fa-route', 'category' => 'support', 'accent' => 'cyan'],
            'movement_editor' => ['icon' => 'fa-pen-to-square', 'category' => 'support', 'accent' => 'sky'],
            'movement_viewer' => ['icon' => 'fa-map-location-dot', 'category' => 'support', 'accent' => 'stone'],
            'default' => ['icon' => 'fa-id-badge', 'category' => 'operations', 'accent' => 'blue'],
        ];
    }

    private function technicalReference(): array
    {
        $relations = ['routes' => ['role.relations.*', 'events.ramadan.*', 'events.bazaars.*'], 'controllers' => ['Agenda controllers', 'MonthlyActivities controllers', 'Ramadan controllers', 'BazaarController']];
        $followup = ['routes' => ['followup.*', 'evaluations.*', 'events.ramadan.iftars.monitoring.*', 'events.bazaars.*'], 'controllers' => ['FollowupWorkspaceController', 'ActivityEvaluationsController', 'RamadanIftarMonitoringController', 'BazaarController']];
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
