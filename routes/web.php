<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/dashboard/admin', 'dashboards.admin')->middleware('role:super_admin')->name('dashboard.admin');
    Route::view('/dashboard/relations', 'dashboards.relations')->middleware('role:relations_manager|relations_officer')->name('dashboard.relations');
    Route::view('/dashboard/programs', 'dashboards.programs')->middleware('role:programs_manager|programs_officer')->name('dashboard.programs');
    Route::view('/dashboard/finance', 'dashboards.finance')->middleware('role:finance_officer')->name('dashboard.finance');
    Route::view('/dashboard/maintenance', 'dashboards.maintenance')->middleware('role:maintenance_officer')->name('dashboard.maintenance');
    Route::view('/dashboard/transport', 'dashboards.transport')->middleware('role:transport_officer')->name('dashboard.transport');
    Route::view('/dashboard/reports', 'dashboards.reports')->middleware('role:reports_viewer')->name('dashboard.reports');
    Route::view('/dashboard/staff', 'dashboards.staff')->middleware('role:staff')->name('dashboard.staff');
});
