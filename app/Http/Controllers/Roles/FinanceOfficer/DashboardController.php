<?php

namespace App\Http\Controllers\Roles\FinanceOfficer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.finance_officer.dashboard');
    }
}
