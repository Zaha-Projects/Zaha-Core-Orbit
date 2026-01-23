<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roleRoutes = [
            'super_admin' => 'dashboard.admin',
            'relations_manager' => 'dashboard.relations',
            'relations_officer' => 'dashboard.relations',
            'programs_manager' => 'dashboard.programs',
            'programs_officer' => 'dashboard.programs',
            'finance_officer' => 'dashboard.finance',
            'maintenance_officer' => 'dashboard.maintenance',
            'transport_officer' => 'dashboard.transport',
            'reports_viewer' => 'dashboard.reports',
            'staff' => 'dashboard.staff',
        ];

        foreach ($roleRoutes as $role => $route) {
            if ($user->hasRole($role)) {
                return redirect()->route($route);
            }
        }

        return view('dashboard');
    }
}
