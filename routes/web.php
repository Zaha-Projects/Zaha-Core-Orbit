<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Roles\FinanceOfficer\DashboardController as FinanceOfficerDashboardController;
use App\Http\Controllers\Roles\MaintenanceOfficer\DashboardController as MaintenanceOfficerDashboardController;
use App\Http\Controllers\Roles\ProgramsManager\DashboardController as ProgramsManagerDashboardController;
use App\Http\Controllers\Roles\ProgramsOfficer\DashboardController as ProgramsOfficerDashboardController;
use App\Http\Controllers\Roles\RelationsManager\DashboardController as RelationsManagerDashboardController;
use App\Http\Controllers\Roles\RelationsOfficer\DashboardController as RelationsOfficerDashboardController;
use App\Http\Controllers\Roles\ReportsViewer\DashboardController as ReportsViewerDashboardController;
use App\Http\Controllers\Roles\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Roles\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Roles\SuperAdmin\RolesManagementController as SuperAdminRolesManagementController;
use App\Http\Controllers\Roles\SuperAdmin\UsersManagementController as SuperAdminUsersManagementController;
use App\Http\Controllers\Roles\SuperAdmin\ApprovalsController as SuperAdminApprovalsController;
use App\Http\Controllers\Roles\SuperAdmin\ReportsController as SuperAdminReportsController;
use App\Http\Controllers\Roles\SuperAdmin\BranchesManagementController as SuperAdminBranchesManagementController;
use App\Http\Controllers\Roles\SuperAdmin\CentersManagementController as SuperAdminCentersManagementController;
use App\Http\Controllers\Roles\TransportOfficer\DashboardController as TransportOfficerDashboardController;
use App\Http\Controllers\Roles\Relations\AgendaEventsController as RelationsAgendaEventsController;
use App\Http\Controllers\Roles\Relations\AgendaApprovalsController as RelationsAgendaApprovalsController;

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
    Route::get('/dashboard/admin', [SuperAdminDashboardController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.dashboard');
    Route::get('/dashboard/admin/reports', [SuperAdminReportsController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.reports');
    Route::get('/dashboard/admin/roles', [SuperAdminRolesManagementController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.roles');
    Route::post('/dashboard/admin/roles', [SuperAdminRolesManagementController::class, 'store'])->middleware('role:super_admin')->name('role.super_admin.roles.store');
    Route::put('/dashboard/admin/roles/{role}', [SuperAdminRolesManagementController::class, 'update'])->middleware('role:super_admin')->name('role.super_admin.roles.update');
    Route::get('/dashboard/admin/users', [SuperAdminUsersManagementController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.users');
    Route::post('/dashboard/admin/users', [SuperAdminUsersManagementController::class, 'store'])->middleware('role:super_admin')->name('role.super_admin.users.store');
    Route::put('/dashboard/admin/users/{user}', [SuperAdminUsersManagementController::class, 'update'])->middleware('role:super_admin')->name('role.super_admin.users.update');
    Route::delete('/dashboard/admin/users/{user}', [SuperAdminUsersManagementController::class, 'destroy'])->middleware('role:super_admin')->name('role.super_admin.users.destroy');
    Route::get('/dashboard/admin/branches', [SuperAdminBranchesManagementController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.branches');
    Route::post('/dashboard/admin/branches', [SuperAdminBranchesManagementController::class, 'store'])->middleware('role:super_admin')->name('role.super_admin.branches.store');
    Route::put('/dashboard/admin/branches/{branch}', [SuperAdminBranchesManagementController::class, 'update'])->middleware('role:super_admin')->name('role.super_admin.branches.update');
    Route::delete('/dashboard/admin/branches/{branch}', [SuperAdminBranchesManagementController::class, 'destroy'])->middleware('role:super_admin')->name('role.super_admin.branches.destroy');
    Route::get('/dashboard/admin/centers', [SuperAdminCentersManagementController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.centers');
    Route::post('/dashboard/admin/centers', [SuperAdminCentersManagementController::class, 'store'])->middleware('role:super_admin')->name('role.super_admin.centers.store');
    Route::put('/dashboard/admin/centers/{center}', [SuperAdminCentersManagementController::class, 'update'])->middleware('role:super_admin')->name('role.super_admin.centers.update');
    Route::delete('/dashboard/admin/centers/{center}', [SuperAdminCentersManagementController::class, 'destroy'])->middleware('role:super_admin')->name('role.super_admin.centers.destroy');
    Route::get('/dashboard/admin/approvals', [SuperAdminApprovalsController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.approvals');
    Route::get('/dashboard/relations/manager', [RelationsManagerDashboardController::class, 'index'])->middleware('role:relations_manager')->name('role.relations_manager.dashboard');
    Route::get('/dashboard/relations/officer', [RelationsOfficerDashboardController::class, 'index'])->middleware('role:relations_officer')->name('role.relations_officer.dashboard');
    Route::get('/dashboard/relations/agenda', [RelationsAgendaEventsController::class, 'index'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.index');
    Route::get('/dashboard/relations/agenda/create', [RelationsAgendaEventsController::class, 'create'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.create');
    Route::post('/dashboard/relations/agenda', [RelationsAgendaEventsController::class, 'store'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.store');
    Route::get('/dashboard/relations/agenda/{agendaEvent}/edit', [RelationsAgendaEventsController::class, 'edit'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.edit');
    Route::put('/dashboard/relations/agenda/{agendaEvent}', [RelationsAgendaEventsController::class, 'update'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.update');
    Route::patch('/dashboard/relations/agenda/{agendaEvent}/submit', [RelationsAgendaEventsController::class, 'submit'])->middleware('role:relations_manager|relations_officer')->name('role.relations.agenda.submit');
    Route::get('/dashboard/relations/agenda/approvals', [RelationsAgendaApprovalsController::class, 'index'])->middleware('role:relations_manager|relations_officer')->name('role.relations.approvals.index');
    Route::put('/dashboard/relations/agenda/approvals/{agendaEvent}', [RelationsAgendaApprovalsController::class, 'update'])->middleware('role:relations_manager|relations_officer')->name('role.relations.approvals.update');
    Route::get('/dashboard/programs/manager', [ProgramsManagerDashboardController::class, 'index'])->middleware('role:programs_manager')->name('role.programs_manager.dashboard');
    Route::get('/dashboard/programs/officer', [ProgramsOfficerDashboardController::class, 'index'])->middleware('role:programs_officer')->name('role.programs_officer.dashboard');
    Route::get('/dashboard/finance', [FinanceOfficerDashboardController::class, 'index'])->middleware('role:finance_officer')->name('role.finance_officer.dashboard');
    Route::get('/dashboard/maintenance', [MaintenanceOfficerDashboardController::class, 'index'])->middleware('role:maintenance_officer')->name('role.maintenance_officer.dashboard');
    Route::get('/dashboard/transport', [TransportOfficerDashboardController::class, 'index'])->middleware('role:transport_officer')->name('role.transport_officer.dashboard');
    Route::get('/dashboard/reports', [ReportsViewerDashboardController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports_viewer.dashboard');
    Route::get('/dashboard/staff', [StaffDashboardController::class, 'index'])->middleware('role:staff')->name('role.staff.dashboard');
});
