<?php

namespace App\Http\Controllers\Roles\RelationsManager;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('roles.relations_manager.dashboard');
    }
}
