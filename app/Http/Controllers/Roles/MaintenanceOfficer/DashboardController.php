<?php

namespace App\Http\Controllers\Roles\MaintenanceOfficer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.maintenance_officer.dashboard');
    }
}
