<?php

namespace App\Http\Controllers\Roles\RelationsOfficer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.relations_officer.dashboard');
    }
}
