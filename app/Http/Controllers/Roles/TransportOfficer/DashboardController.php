<?php

namespace App\Http\Controllers\Roles\TransportOfficer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.transport_officer.dashboard');
    }
}
