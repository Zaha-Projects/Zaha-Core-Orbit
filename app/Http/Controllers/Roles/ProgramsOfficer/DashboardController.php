<?php

namespace App\Http\Controllers\Roles\ProgramsOfficer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.programs_officer.dashboard');
    }
}
