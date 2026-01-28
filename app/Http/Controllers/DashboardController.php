<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roleRoutes = [
            'super_admin' => 'role.super_admin.dashboard',
            'relations_manager' => 'role.relations_manager.dashboard',
            'relations_officer' => 'role.relations_officer.dashboard',
            'programs_manager' => 'role.programs_manager.dashboard',
            'programs_officer' => 'role.programs_officer.dashboard',
            'finance_officer' => 'role.finance_officer.dashboard',
            'maintenance_officer' => 'role.maintenance_officer.dashboard',
            'transport_officer' => 'role.transport_officer.dashboard',
            'reports_viewer' => 'role.reports_viewer.dashboard',
            'staff' => 'role.staff.dashboard',
        ];

        foreach ($roleRoutes as $role => $route) {
            if ($user->hasRole($role)) {
                return redirect()->route($route);
            }
        }

        return view('dashboard');
    }
}
