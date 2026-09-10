<?php

namespace Tests\Feature;

use Tests\TestCase;

class EventsPhaseZeroRouteContractTest extends TestCase
{
    /**
     * @dataProvider monthlyActivityRouteContracts
     */
    public function test_monthly_activity_http_contracts_are_stable(
        string $name,
        string $uri,
        string $method,
        array $middleware
    ): void {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertNotNull($route, "Missing named route [{$name}].");
        $this->assertSame($uri, $route->uri());
        $this->assertContains($method, $route->methods());

        $assignedMiddleware = $route->gatherMiddleware();
        foreach ($middleware as $expectedMiddleware) {
            $this->assertContains($expectedMiddleware, $assignedMiddleware, "Route [{$name}] lost middleware [{$expectedMiddleware}].");
        }
    }

    public function monthlyActivityRouteContracts(): array
    {
        return [
            'index' => ['role.relations.activities.index', 'dashboard/relations/monthly-activities', 'GET', ['auth', 'branch.isolation']],
            'calendar' => ['role.relations.activities.calendar', 'dashboard/relations/monthly-activities/calendar', 'GET', ['auth', 'branch.isolation']],
            'trash' => ['role.relations.activities.trash', 'dashboard/relations/monthly-activities/trash', 'GET', ['auth', 'branch.isolation']],
            'returned feedback' => ['role.relations.activities.returned_feedback', 'dashboard/relations/monthly-activities/returned-feedback', 'GET', ['auth', 'branch.isolation']],
            'post-execution feedback' => ['role.relations.activities.post_execution_feedback', 'dashboard/relations/monthly-activities/post-execution-feedback', 'GET', ['auth', 'branch.isolation']],
            'restore' => ['role.relations.activities.trash.restore', 'dashboard/relations/monthly-activities/trash/{monthlyActivity}/restore', 'PATCH', ['auth', 'branch.isolation']],
            'agenda sync' => ['role.relations.activities.sync_from_agenda', 'dashboard/relations/monthly-activities/sync-from-agenda', 'POST', ['auth', 'branch.isolation']],
            'create' => ['role.relations.activities.create', 'dashboard/relations/monthly-activities/create', 'GET', ['auth', 'branch.isolation']],
            'store' => ['role.relations.activities.store', 'dashboard/relations/monthly-activities', 'POST', ['auth', 'branch.isolation']],
            'show deleted' => ['role.relations.activities.deleted.show', 'dashboard/relations/monthly-activities/deleted/{monthlyActivity}', 'GET', ['auth']],
            'edit' => ['role.relations.activities.edit', 'dashboard/relations/monthly-activities/{monthlyActivity}/edit', 'GET', ['auth']],
            'show' => ['role.relations.activities.show', 'dashboard/relations/monthly-activities/{monthlyActivity}', 'GET', ['auth']],
            'update' => ['role.relations.activities.update', 'dashboard/relations/monthly-activities/{monthlyActivity}', 'PUT', ['auth']],
            'destroy' => ['role.relations.activities.destroy', 'dashboard/relations/monthly-activities/{monthlyActivity}', 'DELETE', ['auth']],
            'submit' => ['role.relations.activities.submit', 'dashboard/relations/monthly-activities/{monthlyActivity}/submit', 'PATCH', ['auth']],
            'close' => ['role.relations.activities.close', 'dashboard/relations/monthly-activities/{monthlyActivity}/close', 'PATCH', ['auth']],
            'approval queue' => ['role.programs.approvals.index', 'dashboard/programs/monthly-activities/approvals', 'GET', ['auth']],
            'approval details' => ['role.programs.approvals.details', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/details', 'GET', ['auth']],
            'approval decision' => ['role.programs.approvals.update', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}', 'PUT', ['auth']],
            'post-execution decision' => ['role.programs.approvals.post_execution_decision', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/post-execution-decision', 'PATCH', ['auth']],
            'execution-need decision' => ['role.programs.approvals.execution_needs.update', 'dashboard/programs/monthly-activities/approvals/{monthlyActivity}/execution-need', 'PUT', ['auth']],
            'delete-request decision' => ['role.programs.approvals.delete_requests.update', 'dashboard/programs/monthly-activities/approvals/delete-requests/{deleteRequest}', 'PUT', ['auth']],
            'edit-request decision' => ['role.programs.approvals.edit_requests.update', 'dashboard/programs/monthly-activities/approvals/edit-requests/{editRequest}', 'PUT', ['auth']],
        ];
    }

    /**
     * @dataProvider agendaRouteContracts
     */
    public function test_agenda_http_contracts_are_stable(string $name, string $uri, string $method): void
    {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertNotNull($route, "Missing named route [{$name}].");
        $this->assertSame($uri, $route->uri());
        $this->assertContains($method, $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function agendaRouteContracts(): array
    {
        return [
            'index' => ['role.relations.agenda.index', 'dashboard/relations/agenda', 'GET'],
            'create' => ['role.relations.agenda.create', 'dashboard/relations/agenda/create', 'GET'],
            'store' => ['role.relations.agenda.store', 'dashboard/relations/agenda', 'POST'],
            'show' => ['role.relations.agenda.show', 'dashboard/relations/agenda/{agendaEvent}', 'GET'],
            'edit' => ['role.relations.agenda.edit', 'dashboard/relations/agenda/{agendaEvent}/edit', 'GET'],
            'update' => ['role.relations.agenda.update', 'dashboard/relations/agenda/{agendaEvent}', 'PUT'],
            'destroy' => ['role.relations.agenda.destroy', 'dashboard/relations/agenda/{agendaEvent}', 'DELETE'],
            'submit' => ['role.relations.agenda.submit', 'dashboard/relations/agenda/{agendaEvent}/submit', 'PATCH'],
            'unit participation' => ['role.relations.agenda.unit_participation.update', 'dashboard/relations/agenda/{agendaEvent}/unit-participation', 'PATCH'],
            'branch participation' => ['role.relations.agenda.branch_participation.update', 'dashboard/relations/agenda/{agendaEvent}/branch-participation', 'PATCH'],
            'quick subscribe' => ['role.relations.agenda.quick_subscribe', 'dashboard/relations/agenda/{agendaEvent}/quick-subscribe', 'POST'],
            'approval queue' => ['role.relations.approvals.index', 'dashboard/relations/agenda/approvals', 'GET'],
            'approval decision' => ['role.relations.approvals.update', 'dashboard/relations/agenda/approvals/{agendaEvent}', 'PUT'],
        ];
    }
}
