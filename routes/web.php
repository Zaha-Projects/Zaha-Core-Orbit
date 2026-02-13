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
use App\Http\Controllers\Roles\SuperAdmin\DepartmentsManagementController as SuperAdminDepartmentsManagementController;
use App\Http\Controllers\Roles\TransportOfficer\DashboardController as TransportOfficerDashboardController;
use App\Http\Controllers\Roles\Relations\AgendaEventsController as RelationsAgendaEventsController;
use App\Http\Controllers\Roles\Relations\AgendaApprovalsController as RelationsAgendaApprovalsController;
use App\Http\Controllers\Roles\Programs\MonthlyActivitiesController as ProgramsMonthlyActivitiesController;
use App\Http\Controllers\Roles\Programs\MonthlyActivitySuppliesController as ProgramsMonthlyActivitySuppliesController;
use App\Http\Controllers\Roles\Programs\MonthlyActivityTeamController as ProgramsMonthlyActivityTeamController;
use App\Http\Controllers\Roles\Programs\MonthlyActivityAttachmentsController as ProgramsMonthlyActivityAttachmentsController;
use App\Http\Controllers\Roles\Programs\MonthlyActivityApprovalsController as ProgramsMonthlyActivityApprovalsController;
use App\Http\Controllers\Roles\Finance\DonationsCashController as FinanceDonationsCashController;
use App\Http\Controllers\Roles\Finance\BookingsController as FinanceBookingsController;
use App\Http\Controllers\Roles\Finance\ZahaTimeBookingsController as FinanceZahaTimeBookingsController;
use App\Http\Controllers\Roles\Finance\PaymentsController as FinancePaymentsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceRequestsController as MaintenanceRequestsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceWorkDetailsController as MaintenanceWorkDetailsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceAttachmentsController as MaintenanceAttachmentsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceApprovalsController as MaintenanceApprovalsController;
use App\Http\Controllers\Roles\Transport\VehiclesController as TransportVehiclesController;
use App\Http\Controllers\Roles\Transport\DriversController as TransportDriversController;
use App\Http\Controllers\Roles\Transport\TripsController as TransportTripsController;
use App\Http\Controllers\Roles\Transport\TripSegmentsController as TransportTripSegmentsController;
use App\Http\Controllers\Roles\Transport\TripRoundsController as TransportTripRoundsController;
use App\Http\Controllers\Roles\Reports\ReportsController as ReportsController;
use App\Http\Controllers\Roles\Reports\AgendaReportsController as AgendaReportsController;
use App\Http\Controllers\Roles\Reports\MonthlyReportsController as MonthlyReportsController;
use App\Http\Controllers\Roles\Reports\FinanceReportsController as FinanceReportsController;
use App\Http\Controllers\Roles\Reports\MaintenanceReportsController as MaintenanceReportsController;
use App\Http\Controllers\Roles\Reports\TransportReportsController as TransportReportsController;
use App\Http\Controllers\Roles\Staff\StaffAgendaController as StaffAgendaController;
use App\Http\Controllers\Roles\Staff\StaffMonthlyActivitiesController as StaffMonthlyActivitiesController;

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
    Route::get('/dashboard/admin/departments', [SuperAdminDepartmentsManagementController::class, 'index'])->middleware('role_or_permission:super_admin|departments.view')->name('role.super_admin.departments');
    Route::post('/dashboard/admin/departments', [SuperAdminDepartmentsManagementController::class, 'store'])->middleware('role_or_permission:super_admin|departments.manage')->name('role.super_admin.departments.store');
    Route::put('/dashboard/admin/departments/{department}', [SuperAdminDepartmentsManagementController::class, 'update'])->middleware('role_or_permission:super_admin|departments.manage')->name('role.super_admin.departments.update');
    Route::delete('/dashboard/admin/departments/{department}', [SuperAdminDepartmentsManagementController::class, 'destroy'])->middleware('role_or_permission:super_admin|departments.manage')->name('role.super_admin.departments.destroy');
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
    Route::get('/dashboard/programs/monthly-activities', [ProgramsMonthlyActivitiesController::class, 'index'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.index');
    Route::get('/dashboard/programs/monthly-activities/create', [ProgramsMonthlyActivitiesController::class, 'create'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.create');
    Route::post('/dashboard/programs/monthly-activities', [ProgramsMonthlyActivitiesController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.store');
    Route::get('/dashboard/programs/monthly-activities/{monthlyActivity}/edit', [ProgramsMonthlyActivitiesController::class, 'edit'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.edit');
    Route::put('/dashboard/programs/monthly-activities/{monthlyActivity}', [ProgramsMonthlyActivitiesController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.update');
    Route::patch('/dashboard/programs/monthly-activities/{monthlyActivity}/submit', [ProgramsMonthlyActivitiesController::class, 'submit'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.submit');
    Route::patch('/dashboard/programs/monthly-activities/{monthlyActivity}/close', [ProgramsMonthlyActivitiesController::class, 'close'])->middleware('role:programs_manager|programs_officer')->name('role.programs.activities.close');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/supplies', [ProgramsMonthlyActivitySuppliesController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.store');
    Route::put('/dashboard/programs/supplies/{monthlyActivitySupply}', [ProgramsMonthlyActivitySuppliesController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.update');
    Route::delete('/dashboard/programs/supplies/{monthlyActivitySupply}', [ProgramsMonthlyActivitySuppliesController::class, 'destroy'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.destroy');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/team', [ProgramsMonthlyActivityTeamController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.store');
    Route::put('/dashboard/programs/team/{monthlyActivityTeam}', [ProgramsMonthlyActivityTeamController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.update');
    Route::delete('/dashboard/programs/team/{monthlyActivityTeam}', [ProgramsMonthlyActivityTeamController::class, 'destroy'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.destroy');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/attachments', [ProgramsMonthlyActivityAttachmentsController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.attachments.store');
    Route::delete('/dashboard/programs/attachments/{monthlyActivityAttachment}', [ProgramsMonthlyActivityAttachmentsController::class, 'destroy'])->middleware('role:programs_manager|programs_officer')->name('role.programs.attachments.destroy');
    Route::get('/dashboard/programs/monthly-activities/approvals', [ProgramsMonthlyActivityApprovalsController::class, 'index'])->middleware('role:programs_manager|programs_officer')->name('role.programs.approvals.index');
    Route::put('/dashboard/programs/monthly-activities/approvals/{monthlyActivity}', [ProgramsMonthlyActivityApprovalsController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.approvals.update');
    Route::get('/dashboard/finance', [FinanceOfficerDashboardController::class, 'index'])->middleware('role:finance_officer')->name('role.finance_officer.dashboard');
    Route::get('/dashboard/finance/donations', [FinanceDonationsCashController::class, 'index'])->middleware('role:finance_officer')->name('role.finance.donations.index');
    Route::get('/dashboard/finance/donations/create', [FinanceDonationsCashController::class, 'create'])->middleware('role:finance_officer')->name('role.finance.donations.create');
    Route::post('/dashboard/finance/donations', [FinanceDonationsCashController::class, 'store'])->middleware('role:finance_officer')->name('role.finance.donations.store');
    Route::get('/dashboard/finance/donations/{donationCash}/edit', [FinanceDonationsCashController::class, 'edit'])->middleware('role:finance_officer')->name('role.finance.donations.edit');
    Route::put('/dashboard/finance/donations/{donationCash}', [FinanceDonationsCashController::class, 'update'])->middleware('role:finance_officer')->name('role.finance.donations.update');
    Route::get('/dashboard/finance/bookings', [FinanceBookingsController::class, 'index'])->middleware('role:finance_officer')->name('role.finance.bookings.index');
    Route::get('/dashboard/finance/bookings/create', [FinanceBookingsController::class, 'create'])->middleware('role:finance_officer')->name('role.finance.bookings.create');
    Route::post('/dashboard/finance/bookings', [FinanceBookingsController::class, 'store'])->middleware('role:finance_officer')->name('role.finance.bookings.store');
    Route::get('/dashboard/finance/bookings/{booking}/edit', [FinanceBookingsController::class, 'edit'])->middleware('role:finance_officer')->name('role.finance.bookings.edit');
    Route::put('/dashboard/finance/bookings/{booking}', [FinanceBookingsController::class, 'update'])->middleware('role:finance_officer')->name('role.finance.bookings.update');
    Route::get('/dashboard/finance/zaha-time', [FinanceZahaTimeBookingsController::class, 'index'])->middleware('role:finance_officer')->name('role.finance.zaha_time.index');
    Route::get('/dashboard/finance/zaha-time/create', [FinanceZahaTimeBookingsController::class, 'create'])->middleware('role:finance_officer')->name('role.finance.zaha_time.create');
    Route::post('/dashboard/finance/zaha-time', [FinanceZahaTimeBookingsController::class, 'store'])->middleware('role:finance_officer')->name('role.finance.zaha_time.store');
    Route::get('/dashboard/finance/zaha-time/{zahaTimeBooking}/edit', [FinanceZahaTimeBookingsController::class, 'edit'])->middleware('role:finance_officer')->name('role.finance.zaha_time.edit');
    Route::put('/dashboard/finance/zaha-time/{zahaTimeBooking}', [FinanceZahaTimeBookingsController::class, 'update'])->middleware('role:finance_officer')->name('role.finance.zaha_time.update');
    Route::get('/dashboard/finance/payments', [FinancePaymentsController::class, 'index'])->middleware('role:finance_officer')->name('role.finance.payments.index');
    Route::post('/dashboard/finance/payments', [FinancePaymentsController::class, 'store'])->middleware('role:finance_officer')->name('role.finance.payments.store');
    Route::put('/dashboard/finance/payments/{payment}', [FinancePaymentsController::class, 'update'])->middleware('role:finance_officer')->name('role.finance.payments.update');
    Route::get('/dashboard/maintenance/requests', [MaintenanceRequestsController::class, 'index'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.index');
    Route::get('/dashboard/maintenance/requests/create', [MaintenanceRequestsController::class, 'create'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.create');
    Route::post('/dashboard/maintenance/requests', [MaintenanceRequestsController::class, 'store'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.store');
    Route::get('/dashboard/maintenance/requests/{maintenanceRequest}/edit', [MaintenanceRequestsController::class, 'edit'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.edit');
    Route::put('/dashboard/maintenance/requests/{maintenanceRequest}', [MaintenanceRequestsController::class, 'update'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.update');
    Route::patch('/dashboard/maintenance/requests/{maintenanceRequest}/close', [MaintenanceRequestsController::class, 'close'])->middleware('role:maintenance_officer')->name('role.maintenance.requests.close');
    Route::post('/dashboard/maintenance/requests/{maintenanceRequest}/work-details', [MaintenanceWorkDetailsController::class, 'store'])->middleware('role:maintenance_officer')->name('role.maintenance.work_details.store');
    Route::put('/dashboard/maintenance/work-details/{maintenanceWorkDetail}', [MaintenanceWorkDetailsController::class, 'update'])->middleware('role:maintenance_officer')->name('role.maintenance.work_details.update');
    Route::post('/dashboard/maintenance/requests/{maintenanceRequest}/attachments', [MaintenanceAttachmentsController::class, 'store'])->middleware('role:maintenance_officer')->name('role.maintenance.attachments.store');
    Route::delete('/dashboard/maintenance/attachments/{maintenanceAttachment}', [MaintenanceAttachmentsController::class, 'destroy'])->middleware('role:maintenance_officer')->name('role.maintenance.attachments.destroy');
    Route::get('/dashboard/maintenance/approvals', [MaintenanceApprovalsController::class, 'index'])->middleware('role:maintenance_officer')->name('role.maintenance.approvals.index');
    Route::put('/dashboard/maintenance/approvals/{maintenanceRequest}', [MaintenanceApprovalsController::class, 'update'])->middleware('role:maintenance_officer')->name('role.maintenance.approvals.update');
    Route::get('/dashboard/maintenance', [MaintenanceOfficerDashboardController::class, 'index'])->middleware('role:maintenance_officer')->name('role.maintenance_officer.dashboard');
    Route::get('/dashboard/transport/vehicles', [TransportVehiclesController::class, 'index'])->middleware('role:transport_officer')->name('role.transport.vehicles.index');
    Route::get('/dashboard/transport/vehicles/create', [TransportVehiclesController::class, 'create'])->middleware('role:transport_officer')->name('role.transport.vehicles.create');
    Route::post('/dashboard/transport/vehicles', [TransportVehiclesController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.vehicles.store');
    Route::get('/dashboard/transport/vehicles/{vehicle}/edit', [TransportVehiclesController::class, 'edit'])->middleware('role:transport_officer')->name('role.transport.vehicles.edit');
    Route::put('/dashboard/transport/vehicles/{vehicle}', [TransportVehiclesController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.vehicles.update');
    Route::get('/dashboard/transport/drivers', [TransportDriversController::class, 'index'])->middleware('role:transport_officer')->name('role.transport.drivers.index');
    Route::get('/dashboard/transport/drivers/create', [TransportDriversController::class, 'create'])->middleware('role:transport_officer')->name('role.transport.drivers.create');
    Route::post('/dashboard/transport/drivers', [TransportDriversController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.drivers.store');
    Route::get('/dashboard/transport/drivers/{driver}/edit', [TransportDriversController::class, 'edit'])->middleware('role:transport_officer')->name('role.transport.drivers.edit');
    Route::put('/dashboard/transport/drivers/{driver}', [TransportDriversController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.drivers.update');
    Route::get('/dashboard/transport/trips', [TransportTripsController::class, 'index'])->middleware('role:transport_officer')->name('role.transport.trips.index');
    Route::get('/dashboard/transport/trips/create', [TransportTripsController::class, 'create'])->middleware('role:transport_officer')->name('role.transport.trips.create');
    Route::post('/dashboard/transport/trips', [TransportTripsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.trips.store');
    Route::get('/dashboard/transport/trips/{trip}/edit', [TransportTripsController::class, 'edit'])->middleware('role:transport_officer')->name('role.transport.trips.edit');
    Route::put('/dashboard/transport/trips/{trip}', [TransportTripsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.trips.update');
    Route::patch('/dashboard/transport/trips/{trip}/close', [TransportTripsController::class, 'close'])->middleware('role:transport_officer')->name('role.transport.trips.close');
    Route::post('/dashboard/transport/trips/{trip}/segments', [TransportTripSegmentsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.segments.store');
    Route::put('/dashboard/transport/segments/{tripSegment}', [TransportTripSegmentsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.segments.update');
    Route::delete('/dashboard/transport/segments/{tripSegment}', [TransportTripSegmentsController::class, 'destroy'])->middleware('role:transport_officer')->name('role.transport.segments.destroy');
    Route::post('/dashboard/transport/trips/{trip}/rounds', [TransportTripRoundsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.rounds.store');
    Route::put('/dashboard/transport/rounds/{tripRound}', [TransportTripRoundsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.rounds.update');
    Route::delete('/dashboard/transport/rounds/{tripRound}', [TransportTripRoundsController::class, 'destroy'])->middleware('role:transport_officer')->name('role.transport.rounds.destroy');
    Route::get('/dashboard/transport', [TransportOfficerDashboardController::class, 'index'])->middleware('role:transport_officer')->name('role.transport_officer.dashboard');
    Route::get('/dashboard/reports/overview', [ReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.index');
    Route::post('/dashboard/reports/overview/export', [ReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.export');
    Route::get('/dashboard/reports/agenda', [AgendaReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.agenda.index');
    Route::post('/dashboard/reports/agenda/export', [AgendaReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.agenda.export');
    Route::get('/dashboard/reports/monthly', [MonthlyReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.monthly.index');
    Route::post('/dashboard/reports/monthly/export', [MonthlyReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.monthly.export');
    Route::get('/dashboard/reports/finance', [FinanceReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.finance.index');
    Route::post('/dashboard/reports/finance/export', [FinanceReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.finance.export');
    Route::get('/dashboard/reports/maintenance', [MaintenanceReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.maintenance.index');
    Route::post('/dashboard/reports/maintenance/export', [MaintenanceReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.maintenance.export');
    Route::get('/dashboard/reports/transport', [TransportReportsController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports.transport.index');
    Route::post('/dashboard/reports/transport/export', [TransportReportsController::class, 'export'])->middleware('role:reports_viewer')->name('role.reports.transport.export');
    Route::get('/dashboard/reports', [ReportsViewerDashboardController::class, 'index'])->middleware('role:reports_viewer')->name('role.reports_viewer.dashboard');
    Route::get('/dashboard/staff/agenda', [StaffAgendaController::class, 'index'])->middleware('role:staff')->name('role.staff.agenda.index');
    Route::get('/dashboard/staff/activities', [StaffMonthlyActivitiesController::class, 'index'])->middleware('role:staff')->name('role.staff.activities.index');
    Route::get('/dashboard/staff', [StaffDashboardController::class, 'index'])->middleware('role:staff')->name('role.staff.dashboard');
});
