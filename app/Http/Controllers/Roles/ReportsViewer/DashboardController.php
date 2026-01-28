<?php

namespace App\Http\Controllers\Roles\ReportsViewer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.reports_viewer.dashboard');
    }
}
