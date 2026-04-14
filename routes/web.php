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
use App\Http\Controllers\Web\Access\RolesController as SuperAdminRolesManagementController;
use App\Http\Controllers\Web\Access\UsersController as SuperAdminUsersManagementController;
use App\Http\Controllers\Web\Access\ApprovalsController as SuperAdminApprovalsController;
use App\Http\Controllers\Web\Access\WorkflowsController as SuperAdminWorkflowsController;
use App\Http\Controllers\Roles\SuperAdmin\ReportsController as SuperAdminReportsController;
use App\Http\Controllers\Web\Access\BranchesController as SuperAdminBranchesManagementController;
use App\Http\Controllers\Roles\TransportOfficer\DashboardController as TransportOfficerDashboardController;
use App\Http\Controllers\Web\Agenda\AgendaEventsController as RelationsAgendaEventsController;
use App\Http\Controllers\Web\Agenda\AgendaApprovalsController as RelationsAgendaApprovalsController;
use App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesController as ProgramsMonthlyActivitiesController;
use App\Http\Controllers\Web\MonthlyActivities\EventLookupsController;
use App\Http\Controllers\Web\MonthlyActivities\WorkshopsRequestsController;
use App\Http\Controllers\Web\MonthlyActivities\CommunicationsRequestsController;
use App\Http\Controllers\Roles\Programs\MonthlyActivitySuppliesController as ProgramsMonthlyActivitySuppliesController;
use App\Http\Controllers\Roles\Programs\MonthlyActivityTeamController as ProgramsMonthlyActivityTeamController;
use App\Http\Controllers\Roles\Programs\MonthlyActivityAttachmentsController as ProgramsMonthlyActivityAttachmentsController;
use App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesApprovalsController as ProgramsMonthlyActivityApprovalsController;
use App\Http\Controllers\Web\Finance\DonationsController as FinanceDonationsCashController;
use App\Http\Controllers\Web\Finance\BookingsController as FinanceBookingsController;
use App\Http\Controllers\Web\Finance\ZahaTimeController as FinanceZahaTimeBookingsController;
use App\Http\Controllers\Web\Finance\PaymentsController as FinancePaymentsController;
use App\Http\Controllers\Web\Maintenance\MaintenanceRequestsController as MaintenanceRequestsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceWorkDetailsController as MaintenanceWorkDetailsController;
use App\Http\Controllers\Roles\Maintenance\MaintenanceAttachmentsController as MaintenanceAttachmentsController;
use App\Http\Controllers\Web\Maintenance\MaintenanceApprovalsController as MaintenanceApprovalsController;
use App\Http\Controllers\Web\Transport\VehiclesController as TransportVehiclesController;
use App\Http\Controllers\Web\Transport\DriversController as TransportDriversController;
use App\Http\Controllers\Web\Transport\TripsController as TransportTripsController;
use App\Http\Controllers\Web\Transport\MovementsController as TransportMovementsController;
use App\Http\Controllers\Roles\Transport\TripSegmentsController as TransportTripSegmentsController;
use App\Http\Controllers\Roles\Transport\TripRoundsController as TransportTripRoundsController;
use App\Http\Controllers\Roles\Transport\TransportRequestsController as TransportTransportRequestsController;
use App\Http\Controllers\Web\Reports\ReportsController as ReportsController;
use App\Http\Controllers\Web\Reports\AgendaReportsController as AgendaReportsController;
use App\Http\Controllers\Web\Reports\MonthlyReportsController as MonthlyReportsController;
use App\Http\Controllers\Web\Reports\FinanceReportsController as FinanceReportsController;
use App\Http\Controllers\Web\Reports\MaintenanceReportsController as MaintenanceReportsController;
use App\Http\Controllers\Web\Reports\TransportReportsController as TransportReportsController;
use App\Http\Controllers\Web\Reports\MonthlyKpisController as MonthlyKpisController;
use App\Http\Controllers\Roles\Staff\StaffAgendaController as StaffAgendaController;
use App\Http\Controllers\Roles\Staff\StaffMonthlyActivitiesController as StaffMonthlyActivitiesController;
use App\Http\Controllers\Web\Enterprise\EnterpriseDashboardController;
use App\Http\Controllers\Web\Enterprise\EnterpriseReportsController;
use App\Http\Controllers\Web\Enterprise\NotificationsController;
use App\Http\Controllers\Web\Enterprise\ArchiveController;

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
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


Route::post('/ui/theme/{theme}', function (string $theme) {
    abort_unless(in_array($theme, ['light', 'dark'], true), 404);
    session(['ui.theme' => $theme]);

    return back();
})->name('ui.theme');

Route::post('/ui/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ar', 'en'], true), 404);
    session(['ui.locale' => $locale]);
    app()->setLocale($locale);

    return back();
})->name('ui.locale');

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
    Route::get('/dashboard/admin/roles', [SuperAdminRolesManagementController::class, 'index'])->middleware('role_or_permission:super_admin|roles.view')->name('role.super_admin.roles');
    Route::post('/dashboard/admin/roles', [SuperAdminRolesManagementController::class, 'store'])->middleware('role_or_permission:super_admin|roles.manage')->name('role.super_admin.roles.store');
    Route::put('/dashboard/admin/roles/{role}', [SuperAdminRolesManagementController::class, 'update'])->middleware('role_or_permission:super_admin|roles.manage')->name('role.super_admin.roles.update');
    Route::delete('/dashboard/admin/roles/{role}', [SuperAdminRolesManagementController::class, 'destroy'])->middleware('role_or_permission:super_admin|roles.manage')->name('role.super_admin.roles.destroy');
    Route::get('/dashboard/admin/workflows', [SuperAdminWorkflowsController::class, 'index'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflows');
    Route::post('/dashboard/admin/workflows', [SuperAdminWorkflowsController::class, 'store'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflows.store');
    Route::put('/dashboard/admin/workflows/{workflow}', [SuperAdminWorkflowsController::class, 'update'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflows.update');
    Route::delete('/dashboard/admin/workflows/{workflow}', [SuperAdminWorkflowsController::class, 'destroy'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflows.destroy');
    Route::post('/dashboard/admin/workflows/{workflow}/steps', [SuperAdminWorkflowsController::class, 'storeStep'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflow_steps.store');
    Route::put('/dashboard/admin/workflow-steps/{step}', [SuperAdminWorkflowsController::class, 'updateStep'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflow_steps.update');
    Route::delete('/dashboard/admin/workflow-steps/{step}', [SuperAdminWorkflowsController::class, 'destroyStep'])->middleware('role_or_permission:super_admin|workflows.manage')->name('role.super_admin.workflow_steps.destroy');
    Route::get('/dashboard/admin/users', [SuperAdminUsersManagementController::class, 'index'])->middleware('role_or_permission:super_admin|users.view')->name('role.super_admin.users');
    Route::post('/dashboard/admin/users', [SuperAdminUsersManagementController::class, 'store'])->middleware('role_or_permission:super_admin|users.manage')->name('role.super_admin.users.store');
    Route::put('/dashboard/admin/users/{user}', [SuperAdminUsersManagementController::class, 'update'])->middleware('role_or_permission:super_admin|users.manage')->name('role.super_admin.users.update');
    Route::delete('/dashboard/admin/users/{user}', [SuperAdminUsersManagementController::class, 'destroy'])->middleware('role_or_permission:super_admin|users.manage')->name('role.super_admin.users.destroy');
    Route::get('/dashboard/admin/branches', [SuperAdminBranchesManagementController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.branches');
    Route::post('/dashboard/admin/branches', [SuperAdminBranchesManagementController::class, 'store'])->middleware('role:super_admin')->name('role.super_admin.branches.store');
    Route::put('/dashboard/admin/branches/{branch}', [SuperAdminBranchesManagementController::class, 'update'])->middleware('role:super_admin')->name('role.super_admin.branches.update');
    Route::delete('/dashboard/admin/branches/{branch}', [SuperAdminBranchesManagementController::class, 'destroy'])->middleware('role:super_admin')->name('role.super_admin.branches.destroy');
    Route::get('/dashboard/admin/approvals', [SuperAdminApprovalsController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.approvals');

    Route::get('/dashboard/admin/events-lookups', [EventLookupsController::class, 'index'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.index');
    Route::post('/dashboard/admin/events-lookups/departments', [EventLookupsController::class, 'storeDepartment'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.departments.store');
    Route::put('/dashboard/admin/events-lookups/departments/{department}', [EventLookupsController::class, 'updateDepartment'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.departments.update');
    Route::post('/dashboard/admin/events-lookups/target-groups', [EventLookupsController::class, 'storeTargetGroup'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.target_groups.store');
    Route::put('/dashboard/admin/events-lookups/target-groups/{targetGroup}', [EventLookupsController::class, 'updateTargetGroup'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.target_groups.update');
    Route::post('/dashboard/admin/events-lookups/evaluation-questions', [EventLookupsController::class, 'storeEvaluationQuestion'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.evaluation_questions.store');
    Route::put('/dashboard/admin/events-lookups/evaluation-questions/{evaluationQuestion}', [EventLookupsController::class, 'updateEvaluationQuestion'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.evaluation_questions.update');
    Route::post('/dashboard/admin/events-lookups/department-units', [EventLookupsController::class, 'storeDepartmentUnit'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.department_units.store');
    Route::put('/dashboard/admin/events-lookups/department-units/{departmentUnit}', [EventLookupsController::class, 'updateDepartmentUnit'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.department_units.update');
    Route::post('/dashboard/admin/events-lookups/event-categories', [EventLookupsController::class, 'storeEventCategory'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.event_categories.store');
    Route::put('/dashboard/admin/events-lookups/event-categories/{eventCategory}', [EventLookupsController::class, 'updateEventCategory'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.event_categories.update');
    Route::post('/dashboard/admin/events-lookups/status-lookups', [EventLookupsController::class, 'storeStatusLookup'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.status_lookups.store');
    Route::put('/dashboard/admin/events-lookups/status-lookups/{eventStatusLookup}', [EventLookupsController::class, 'updateStatusLookup'])->middleware('role:super_admin')->name('role.super_admin.events_lookups.status_lookups.update');
    Route::get('/dashboard/relations/manager', [RelationsManagerDashboardController::class, 'index'])->middleware('role:relations_manager|super_admin')->name('role.relations_manager.dashboard');
    Route::get('/dashboard/relations/officer', [RelationsOfficerDashboardController::class, 'index'])->middleware('role:relations_officer|super_admin')->name('role.relations_officer.dashboard');
    Route::get('/dashboard/relations/agenda', [RelationsAgendaEventsController::class, 'index'])->middleware('role_or_permission:relations_manager|relations_officer|executive_manager|super_admin|agenda.view')->middleware('branch.isolation')->name('role.relations.agenda.index');
    Route::get('/dashboard/relations/agenda/create', [RelationsAgendaEventsController::class, 'create'])->middleware('role_or_permission:relations_manager|relations_officer|super_admin|agenda.create')->name('role.relations.agenda.create');
    Route::post('/dashboard/relations/agenda', [RelationsAgendaEventsController::class, 'store'])->middleware('role_or_permission:relations_manager|relations_officer|super_admin|agenda.create')->name('role.relations.agenda.store');
    Route::get('/dashboard/relations/agenda/{agendaEvent}/edit', [RelationsAgendaEventsController::class, 'edit'])->middleware('role_or_permission:relations_manager|relations_officer|super_admin|agenda.update')->whereNumber('agendaEvent')->name('role.relations.agenda.edit');
    Route::put('/dashboard/relations/agenda/{agendaEvent}', [RelationsAgendaEventsController::class, 'update'])->middleware('role_or_permission:relations_manager|relations_officer|super_admin|agenda.update')->whereNumber('agendaEvent')->name('role.relations.agenda.update');
    Route::patch('/dashboard/relations/agenda/{agendaEvent}/submit', [RelationsAgendaEventsController::class, 'submit'])->middleware('role_or_permission:relations_manager|relations_officer|super_admin|agenda.update')->whereNumber('agendaEvent')->name('role.relations.agenda.submit');
    Route::patch('/dashboard/relations/agenda/{agendaEvent}/unit-participation', [RelationsAgendaEventsController::class, 'updateUnitParticipation'])->middleware('role_or_permission:relations_manager|workshops_secretary|communication_head|programs_manager|super_admin|agenda.participation.update')->whereNumber('agendaEvent')->name('role.relations.agenda.unit_participation.update');
    Route::patch('/dashboard/relations/agenda/{agendaEvent}/branch-participation', [RelationsAgendaEventsController::class, 'updateBranchParticipation'])->middleware('role_or_permission:relations_officer|branch_relations_officer|super_admin|agenda.participation.update')->whereNumber('agendaEvent')->name('role.relations.agenda.branch_participation.update');
    Route::get('/dashboard/relations/agenda/approvals', [RelationsAgendaApprovalsController::class, 'index'])->middleware('role_or_permission:relations_manager|executive_manager|super_admin|agenda.approve')->name('role.relations.approvals.index');
    Route::put('/dashboard/relations/agenda/approvals/{agendaEvent}', [RelationsAgendaApprovalsController::class, 'update'])->middleware('role_or_permission:relations_manager|executive_manager|super_admin|agenda.approve')->name('role.relations.approvals.update');
    Route::get('/dashboard/relations/agenda/{agendaEvent}', [RelationsAgendaEventsController::class, 'show'])->middleware('role_or_permission:relations_manager|relations_officer|executive_manager|super_admin|agenda.view')->middleware('branch.isolation')->whereNumber('agendaEvent')->name('role.relations.agenda.show');
    Route::get('/dashboard/programs/manager', [ProgramsManagerDashboardController::class, 'index'])->middleware('role:programs_manager')->name('role.programs_manager.dashboard');
    Route::get('/dashboard/programs/officer', [ProgramsOfficerDashboardController::class, 'index'])->middleware('role:programs_officer')->name('role.programs_officer.dashboard');
    Route::get('/dashboard/relations/monthly-activities', [ProgramsMonthlyActivitiesController::class, 'index'])->middleware('role_or_permission:relations_manager|relations_officer|branch_relations_officer|super_admin|monthly_activities.view')->middleware('branch.isolation')->name('role.relations.activities.index');
    Route::get('/dashboard/relations/monthly-activities/calendar', [ProgramsMonthlyActivitiesController::class, 'calendar'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|super_admin')->name('role.relations.activities.calendar');
    Route::post('/dashboard/relations/monthly-activities/sync-from-agenda', [ProgramsMonthlyActivitiesController::class, 'syncFromAgenda'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|super_admin')->middleware('branch.isolation')->name('role.relations.activities.sync_from_agenda');
    Route::get('/dashboard/relations/monthly-activities/create', [ProgramsMonthlyActivitiesController::class, 'create'])->middleware('role_or_permission:relations_manager|relations_officer|branch_relations_officer|super_admin|monthly_activities.create')->middleware('branch.isolation')->name('role.relations.activities.create');
    Route::post('/dashboard/relations/monthly-activities', [ProgramsMonthlyActivitiesController::class, 'store'])->middleware('role_or_permission:relations_manager|relations_officer|branch_relations_officer|super_admin|monthly_activities.create')->middleware('branch.isolation')->name('role.relations.activities.store');
    Route::get('/dashboard/relations/monthly-activities/{monthlyActivity}/edit', [ProgramsMonthlyActivitiesController::class, 'edit'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|followup_officer|super_admin')->name('role.relations.activities.edit');
    Route::get('/dashboard/relations/monthly-activities/{monthlyActivity}', [ProgramsMonthlyActivitiesController::class, 'show'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|followup_officer|super_admin')->name('role.relations.activities.show');
    Route::put('/dashboard/relations/monthly-activities/{monthlyActivity}', [ProgramsMonthlyActivitiesController::class, 'update'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|followup_officer|super_admin')->name('role.relations.activities.update');
    Route::patch('/dashboard/relations/monthly-activities/{monthlyActivity}/submit', [ProgramsMonthlyActivitiesController::class, 'submit'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|super_admin')->name('role.relations.activities.submit');
    Route::patch('/dashboard/relations/monthly-activities/{monthlyActivity}/close', [ProgramsMonthlyActivitiesController::class, 'close'])->middleware('role:relations_manager|relations_officer|branch_relations_officer|super_admin')->name('role.relations.activities.close');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/supplies', [ProgramsMonthlyActivitySuppliesController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.store');
    Route::put('/dashboard/programs/supplies/{monthlyActivitySupply}', [ProgramsMonthlyActivitySuppliesController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.update');
    Route::delete('/dashboard/programs/supplies/{monthlyActivitySupply}', [ProgramsMonthlyActivitySuppliesController::class, 'destroy'])->middleware('role:programs_manager|programs_officer')->name('role.programs.supplies.destroy');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/team', [ProgramsMonthlyActivityTeamController::class, 'store'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.store');
    Route::put('/dashboard/programs/team/{monthlyActivityTeam}', [ProgramsMonthlyActivityTeamController::class, 'update'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.update');
    Route::delete('/dashboard/programs/team/{monthlyActivityTeam}', [ProgramsMonthlyActivityTeamController::class, 'destroy'])->middleware('role:programs_manager|programs_officer')->name('role.programs.team.destroy');
    Route::post('/dashboard/programs/monthly-activities/{monthlyActivity}/attachments', [ProgramsMonthlyActivityAttachmentsController::class, 'store'])->middleware('role:programs_manager|programs_officer|relations_manager|relations_officer|branch_relations_officer|super_admin')->name('role.programs.attachments.store');
    Route::delete('/dashboard/programs/attachments/{monthlyActivityAttachment}', [ProgramsMonthlyActivityAttachmentsController::class, 'destroy'])->middleware('role:programs_manager|programs_officer|relations_manager|relations_officer|branch_relations_officer|super_admin')->name('role.programs.attachments.destroy');
    Route::get('/dashboard/programs/monthly-activities/approvals', [ProgramsMonthlyActivityApprovalsController::class, 'index'])->middleware('role_or_permission:relations_officer|relations_manager|programs_officer|programs_manager|executive_manager|monthly_activities.approve')->name('role.programs.approvals.index');

    Route::get('/dashboard/programs/workshops-requests', [WorkshopsRequestsController::class, 'index'])->middleware('role:workshops_secretary|super_admin')->name('role.programs.workshops_requests.index');
    Route::put('/dashboard/programs/workshops-requests/{workshopsRequest}', [WorkshopsRequestsController::class, 'update'])->middleware('role:workshops_secretary|super_admin')->name('role.programs.workshops_requests.update');
    Route::get('/dashboard/programs/communications-requests', [CommunicationsRequestsController::class, 'index'])->middleware('role:communication_head|super_admin')->name('role.programs.communications_requests.index');
    Route::put('/dashboard/programs/communications-requests/{communicationsRequest}', [CommunicationsRequestsController::class, 'update'])->middleware('role:communication_head|super_admin')->name('role.programs.communications_requests.update');

    Route::put('/dashboard/programs/monthly-activities/approvals/{monthlyActivity}', [ProgramsMonthlyActivityApprovalsController::class, 'update'])->middleware('role_or_permission:relations_officer|relations_manager|programs_officer|programs_manager|executive_manager|monthly_activities.approve')->name('role.programs.approvals.update');
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
    Route::get('/dashboard/transport/requests', [TransportTransportRequestsController::class, 'index'])->name('role.transport.requests.index');
    Route::post('/dashboard/transport/requests', [TransportTransportRequestsController::class, 'store'])->name('role.transport.requests.store');
    Route::patch('/dashboard/transport/requests/{transportRequest}/process', [TransportTransportRequestsController::class, 'process'])->middleware('role:transport_officer')->name('role.transport.requests.process');
    Route::patch('/dashboard/transport/requests/{transportRequest}/feedback', [TransportTransportRequestsController::class, 'feedback'])->name('role.transport.requests.feedback');
    Route::get('/dashboard/transport/trips', [TransportTripsController::class, 'index'])->middleware('role:transport_officer')->name('role.transport.trips.index');
    Route::get('/dashboard/transport/trips/create', [TransportTripsController::class, 'create'])->middleware('role:transport_officer')->name('role.transport.trips.create');
    Route::post('/dashboard/transport/trips', [TransportTripsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.trips.store');
    Route::get('/dashboard/transport/trips/{trip}/edit', [TransportTripsController::class, 'edit'])->middleware('role:transport_officer')->name('role.transport.trips.edit');
    Route::put('/dashboard/transport/trips/{trip}', [TransportTripsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.trips.update');
    Route::patch('/dashboard/transport/trips/{trip}/close', [TransportTripsController::class, 'close'])->middleware('role:transport_officer')->name('role.transport.trips.close');
    Route::post('/dashboard/transport/trips/{trip}/segments', [TransportTripSegmentsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.segments.store');
    Route::get('/dashboard/transport/movements', [TransportMovementsController::class, 'index'])->middleware('role:transport_officer|movement_manager|movement_editor|movement_viewer|super_admin')->name('role.transport.movements.index');
    Route::get('/dashboard/transport/movements/create', [TransportMovementsController::class, 'create'])->middleware('role:transport_officer|movement_manager|movement_editor|super_admin')->name('role.transport.movements.create');
    Route::post('/dashboard/transport/movements', [TransportMovementsController::class, 'store'])->middleware('role:transport_officer|movement_manager|movement_editor|super_admin')->name('role.transport.movements.store');
    Route::get('/dashboard/transport/movements/{movementDay}', [TransportMovementsController::class, 'show'])->middleware('role:transport_officer|movement_manager|movement_editor|movement_viewer|super_admin')->name('role.transport.movements.show');
    Route::get('/dashboard/transport/movements/{movementDay}/edit', [TransportMovementsController::class, 'edit'])->middleware('role:transport_officer|movement_manager|movement_editor|super_admin')->name('role.transport.movements.edit');
    Route::put('/dashboard/transport/movements/{movementDay}', [TransportMovementsController::class, 'update'])->middleware('role:transport_officer|movement_manager|movement_editor|super_admin')->name('role.transport.movements.update');
    Route::delete('/dashboard/transport/movements/{movementDay}', [TransportMovementsController::class, 'destroy'])->middleware('role:transport_officer|movement_manager|super_admin')->name('role.transport.movements.destroy');
    Route::put('/dashboard/transport/segments/{tripSegment}', [TransportTripSegmentsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.segments.update');
    Route::delete('/dashboard/transport/segments/{tripSegment}', [TransportTripSegmentsController::class, 'destroy'])->middleware('role:transport_officer')->name('role.transport.segments.destroy');
    Route::post('/dashboard/transport/trips/{trip}/rounds', [TransportTripRoundsController::class, 'store'])->middleware('role:transport_officer')->name('role.transport.rounds.store');
    Route::put('/dashboard/transport/rounds/{tripRound}', [TransportTripRoundsController::class, 'update'])->middleware('role:transport_officer')->name('role.transport.rounds.update');
    Route::delete('/dashboard/transport/rounds/{tripRound}', [TransportTripRoundsController::class, 'destroy'])->middleware('role:transport_officer')->name('role.transport.rounds.destroy');
    Route::get('/dashboard/transport', [TransportOfficerDashboardController::class, 'index'])->middleware('role:transport_officer')->name('role.transport_officer.dashboard');
    Route::get('/dashboard/reports/overview', [ReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.index');
    Route::post('/dashboard/reports/overview/export', [ReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.export');
    Route::get('/dashboard/reports/agenda', [AgendaReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.agenda.index');
    Route::post('/dashboard/reports/agenda/export', [AgendaReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.agenda.export');
    Route::get('/dashboard/reports/monthly', [MonthlyReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.monthly.index');
    Route::post('/dashboard/reports/monthly/export', [MonthlyReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.monthly.export');
    Route::get('/dashboard/reports/finance', [FinanceReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.finance.index');
    Route::post('/dashboard/reports/finance/export', [FinanceReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.finance.export');
    Route::get('/dashboard/reports/maintenance', [MaintenanceReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.maintenance.index');
    Route::post('/dashboard/reports/maintenance/export', [MaintenanceReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.maintenance.export');
    Route::get('/dashboard/reports/transport', [TransportReportsController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.transport.index');
    Route::post('/dashboard/reports/transport/export', [TransportReportsController::class, 'export'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|reports.view')->name('role.reports.transport.export');
    Route::get('/dashboard/reports/kpis', [MonthlyKpisController::class, 'index'])->middleware('role_or_permission:reports_viewer|followup_officer|super_admin|kpi.view')->name('role.reports.kpis.index');
    Route::post('/dashboard/reports/kpis', [MonthlyKpisController::class, 'store'])->middleware('role_or_permission:followup_officer|super_admin|kpi.manage')->name('role.reports.kpis.store');

    Route::get('/dashboard/enterprise', [EnterpriseDashboardController::class, 'index'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.enterprise.dashboard');
    Route::get('/dashboard/enterprise/annual-planning', [EnterpriseDashboardController::class, 'annualPlanning'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.enterprise.annual_planning');
    Route::get('/dashboard/reports/enterprise/branch-performance', [EnterpriseDashboardController::class, 'branchReport'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.reports.enterprise.branch_performance');
    Route::get('/dashboard/reports/enterprise/agenda-export', [EnterpriseReportsController::class, 'exportAgenda'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.reports.enterprise.agenda_export');
    Route::get('/dashboard/reports/enterprise/monthly-export', [EnterpriseReportsController::class, 'exportMonthlyActivities'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.reports.enterprise.monthly_export');
    Route::get('/dashboard/reports/enterprise/approval-export', [EnterpriseReportsController::class, 'exportApprovalReport'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.reports.enterprise.approval_export');
    Route::get('/dashboard/reports/enterprise/printable', [EnterpriseReportsController::class, 'printable'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.reports.enterprise.printable');
    Route::patch('/dashboard/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('role.notifications.read');
    Route::post('/dashboard/archive/year', [ArchiveController::class, 'archiveYear'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.enterprise.archive.year');
    Route::post('/dashboard/archive/year/restore', [ArchiveController::class, 'restoreYear'])->middleware('role:reports_viewer|followup_officer|super_admin')->name('role.enterprise.archive.year.restore');

    Route::get('/dashboard/reports', [ReportsViewerDashboardController::class, 'index'])->middleware('role:reports_viewer|followup_officer')->name('role.reports_viewer.dashboard');
    Route::get('/dashboard/staff/agenda', [StaffAgendaController::class, 'index'])->middleware('role:staff')->name('role.staff.agenda.index');
    Route::get('/dashboard/staff/activities', [StaffMonthlyActivitiesController::class, 'index'])->middleware('role:staff')->name('role.staff.activities.index');
    Route::get('/dashboard/staff', [StaffDashboardController::class, 'index'])->middleware('role:staff')->name('role.staff.dashboard');
});
