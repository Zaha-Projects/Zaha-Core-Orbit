<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\AnnualAgendaEditRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Modules\Events\Support\EventAggregateIdentity;
use App\Services\AdminReports\AdminReportsService;
use App\Services\DynamicWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use ReflectionMethod;
use Tests\TestCase;

class AgendaEventIdentityCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_and_dynamic_resolution_accept_both_agenda_identities(): void
    {
        $workflow = $this->workflow();
        $event = $this->agendaEvent();
        $service = app(DynamicWorkflowService::class);
        $resolveEntity = new ReflectionMethod($service, 'resolveEntity');
        $resolveEntity->setAccessible(true);

        foreach (EventAggregateIdentity::acceptedTypes(AgendaEvent::class) as $identity) {
            $instance = WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => $event->id,
                'status' => 'pending',
            ]);

            $event->unsetRelation('workflowInstance');
            $this->assertSame($instance->id, $event->workflowInstance?->id);
            $this->assertSame(1, WorkflowInstance::query()->count());

            $resolved = $resolveEntity->invoke($service, $instance);
            $this->assertInstanceOf(AgendaEvent::class, $resolved);
            $this->assertSame($event->id, $resolved->id);

            $instance->delete();
        }
    }

    public function test_for_model_reuses_either_identity_and_creates_only_legacy_identity(): void
    {
        $workflow = $this->workflow();
        $event = $this->agendaEvent();
        $service = app(DynamicWorkflowService::class);

        foreach (EventAggregateIdentity::acceptedTypes(AgendaEvent::class) as $identity) {
            $instance = WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => $event->id,
                'status' => 'pending',
            ]);

            $this->assertSame($instance->id, $service->forModel('agenda', $event)?->id);
            $this->assertSame(1, WorkflowInstance::query()->count());
            $instance->delete();
        }

        $created = $service->forModel('agenda', $event);
        $this->assertSame(EventAggregateIdentity::AGENDA_LEGACY, $created?->entity_type);
        $this->assertNotSame(EventAggregateIdentity::AGENDA_CANONICAL, $created?->entity_type);
        $this->assertSame(1, WorkflowInstance::query()->count());
    }

    public function test_for_model_fails_when_both_agenda_identities_exist(): void
    {
        $workflow = $this->workflow();
        $event = $this->agendaEvent();

        foreach (EventAggregateIdentity::acceptedTypes(AgendaEvent::class) as $identity) {
            WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => $event->id,
                'status' => 'pending',
            ]);
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Conflicting workflow identities exist for aggregate %s:%d in workflow %d.',
            AgendaEvent::class,
            $event->id,
            $workflow->id
        ));

        app(DynamicWorkflowService::class)->forModel('agenda', $event);
    }

    public function test_agenda_request_aggregate_reader_accepts_both_identity_values_without_changing_writer_contract(): void
    {
        $event = $this->agendaEvent();

        foreach (EventAggregateIdentity::acceptedTypes(AgendaEvent::class) as $identity) {
            $request = AnnualAgendaEditRequest::query()->create([
                'requester_id' => User::factory()->create()->id,
                'request_type' => 'edit',
                'entity_type' => $identity,
                'entity_id' => $event->id,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            $this->assertSame($identity, $request->entity_type);
            $this->assertSame($event->id, $request->agendaEvent?->id);
            $request->delete();
        }

        $this->assertSame(AgendaEvent::class, EventAggregateIdentity::currentWriteType(AgendaEvent::class));
    }

    public function test_admin_report_combines_legacy_and_canonical_agenda_workflow_identities(): void
    {
        $workflow = $this->workflow();

        foreach (EventAggregateIdentity::acceptedTypes(AgendaEvent::class) as $offset => $identity) {
            WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => 1000 + $offset,
                'status' => 'approved',
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);
        }

        $method = new ReflectionMethod(AdminReportsService::class, 'relationsReport');
        $method->setAccessible(true);
        $report = $method->invoke(app(AdminReportsService::class), (int) now()->year, (int) now()->month, null);
        $agendaSpeed = $report['approval_speed']->firstWhere('module', 'AgendaEvent');

        $this->assertNotNull($agendaSpeed);
        $this->assertSame(2, $agendaSpeed['total']);
        $this->assertSame(1, $report['approval_speed']->where('module', 'AgendaEvent')->count());
    }

    private function agendaEvent(): AgendaEvent
    {
        return AgendaEvent::query()->create([
            'event_date' => now()->toDateString(),
            'month' => (int) now()->month,
            'day' => (int) now()->day,
            'event_name' => 'Agenda identity compatibility',
            'status' => 'draft',
            'created_by' => User::factory()->create()->id,
        ]);
    }

    private function workflow(): Workflow
    {
        return Workflow::query()->create([
            'code' => 'agenda_identity_test_'.uniqid(),
            'module' => 'agenda',
            'name_ar' => 'اختبار هوية الأجندة',
            'name_en' => 'Agenda identity test',
            'is_active' => true,
        ]);
    }
}
