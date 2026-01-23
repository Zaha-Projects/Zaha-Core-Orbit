<?php

namespace App\Http\Controllers\Roles\Staff;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.staff.dashboard');
    }
}
