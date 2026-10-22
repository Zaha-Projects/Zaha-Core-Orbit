<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MonthlyActivityControllerRouteContractTest extends TestCase
{
    /** @dataProvider monthlyRouteProvider */
    public function test_monthly_route_contracts_are_owned_by_focused_event_controllers(
        string $name,
        string $verb,
        string $uri,
        string $controller,
        string $method
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, $name);
        $this->assertContains($verb, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame($controller.'@'.$method, $route->getActionName());
        $this->assertSame($controller, (new \ReflectionMethod($controller, $method))->getDeclaringClass()->getName());
    }

    public function monthlyRouteProvider(): array
    {
        $namespace = 'App\\Modules\\Events\\Http\\Controllers\\MonthlyActivities\\';

        return [
            ['role.super_admin.monthly_activities.change_requests.reports', 'GET', 'dashboard/admin/monthly-activities/change-requests/reports', $namespace.'MonthlyActivityReportsController', 'changeRequestReports'],
            ['followup.monthly-plans', 'GET', 'dashboard/followup/monthly-plans', $namespace.'MonthlyActivitiesBrowseController', 'index'],
            ['role.relations.activities.index', 'GET', 'dashboard/relations/monthly-activities', $namespace.'MonthlyActivitiesBrowseController', 'index'],
            ['role.relations.activities.calendar', 'GET', 'dashboard/relations/monthly-activities/calendar', $namespace.'MonthlyActivityCalendarController', 'calendar'],
            ['role.relations.activities.trash', 'GET', 'dashboard/relations/monthly-activities/trash', $namespace.'MonthlyActivityTrashController', 'trash'],
            ['role.relations.activities.returned_feedback', 'GET', 'dashboard/relations/monthly-activities/returned-feedback', $namespace.'MonthlyActivityFeedbackController', 'returnedFeedback'],
            ['role.relations.activities.post_execution_feedback', 'GET', 'dashboard/relations/monthly-activities/post-execution-feedback', $namespace.'MonthlyActivityFeedbackController', 'postExecutionFeedback'],
            ['role.relations.activities.trash.restore', 'PATCH', 'dashboard/relations/monthly-activities/trash/{monthlyActivity}/restore', $namespace.'MonthlyActivityTrashController', 'restore'],
            ['role.relations.activities.sync_from_agenda', 'POST', 'dashboard/relations/monthly-activities/sync-from-agenda', $namespace.'MonthlyActivityPlanningController', 'syncFromAgenda'],
            ['role.relations.activities.create', 'GET', 'dashboard/relations/monthly-activities/create', $namespace.'MonthlyActivityPlanningController', 'create'],
            ['role.relations.activities.store', 'POST', 'dashboard/relations/monthly-activities', $namespace.'MonthlyActivityPlanningController', 'store'],
            ['role.relations.activities.deleted.show', 'GET', 'dashboard/relations/monthly-activities/deleted/{monthlyActivity}', $namespace.'MonthlyActivityWorkspaceController', 'showDeleted'],
            ['role.relations.activities.edit', 'GET', 'dashboard/relations/monthly-activities/{monthlyActivity}/edit', $namespace.'MonthlyActivityPlanningController', 'edit'],
            ['role.relations.activities.show', 'GET', 'dashboard/relations/monthly-activities/{monthlyActivity}', $namespace.'MonthlyActivityWorkspaceController', 'show'],
            ['role.relations.activities.update', 'PUT', 'dashboard/relations/monthly-activities/{monthlyActivity}', $namespace.'MonthlyActivityPlanningController', 'update'],
            ['role.relations.activities.destroy', 'DELETE', 'dashboard/relations/monthly-activities/{monthlyActivity}', $namespace.'MonthlyActivityTrashController', 'destroy'],
            ['role.relations.activities.submit', 'PATCH', 'dashboard/relations/monthly-activities/{monthlyActivity}/submit', $namespace.'MonthlyActivityLifecycleController', 'submit'],
            ['role.relations.activities.close', 'PATCH', 'dashboard/relations/monthly-activities/{monthlyActivity}/close', $namespace.'MonthlyActivityLifecycleController', 'close'],
            ['role.programs.approvals.index', 'GET', 'dashboard/programs/monthly-activities/approvals', $namespace.'MonthlyActivityApprovalQueueController', 'index'],
            ['role.programs.approvals.post_execution_decision', 'PATCH', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/post-execution-decision', $namespace.'MonthlyActivityPostExecutionDecisionController', 'decidePostExecution'],
            ['role.programs.approvals.details', 'GET', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/details', $namespace.'MonthlyActivityApprovalQueueController', 'details'],
            ['role.programs.approvals.update', 'PUT', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}', $namespace.'MonthlyActivityApprovalDecisionController', 'update'],
            ['role.programs.approvals.delete_requests.update', 'PUT', 'dashboard/programs/monthly-activities/approvals/delete-requests/{deleteRequest}', $namespace.'MonthlyActivityChangeRequestDecisionController', 'decideDeleteRequest'],
            ['role.programs.approvals.edit_requests.update', 'PUT', 'dashboard/programs/monthly-activities/approvals/edit-requests/{editRequest}', $namespace.'MonthlyActivityChangeRequestDecisionController', 'decideEditRequest'],
            ['role.programs.approvals.execution_needs.update', 'PUT', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/execution-need', $namespace.'MonthlyActivityApprovalDecisionController', 'decideExecutionNeed'],
        ];
    }

    public function test_monthly_route_middleware_contracts_remain_in_place(): void
    {
        $expectations = [
            'followup.monthly-plans' => ['permission:followup.monthly_plans.view'],
            'role.relations.activities.index' => ['branch.isolation', 'role_or_permission:relations_manager|relations_officer|volunteer_coordinator|programs_manager|super_admin|monthly_activities.view'],
            'role.relations.activities.create' => ['branch.isolation', 'role_or_permission:relations_manager|relations_officer|super_admin|monthly_activities.create'],
            'role.relations.activities.update' => ['role:relations_manager|relations_officer|supervisor|branch_coordinator|followup_officer|evaluation_officer|volunteer_coordinator|communication_head|transport_officer|movement_manager|administrative_unit_manager|super_admin'],
            'role.relations.activities.destroy' => ['role:relations_manager|relations_officer|supervisor|branch_coordinator|super_admin'],
            'role.relations.activities.submit' => ['role:relations_manager|relations_officer|supervisor|relations_officer|super_admin'],
        ];

        foreach ($expectations as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);
            foreach ($middleware as $item) {
                $this->assertContains($item, $route->middleware(), $name.' lost middleware '.$item);
            }
        }
    }

    public function test_focused_controllers_are_physical_owners_and_legacy_classes_are_retired(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Web/MonthlyActivities/MonthlyActivitiesController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Web/MonthlyActivities/MonthlyActivitiesApprovalsController.php'));

        foreach ($this->monthlyRouteProvider() as $contract) {
            $controller = new \ReflectionClass($contract[3]);
            $this->assertSame(\App\Http\Controllers\Controller::class, $controller->getParentClass()->getName());
            $this->assertSame($contract[3], $controller->getMethod($contract[4])->getDeclaringClass()->getName());
        }
    }
}
