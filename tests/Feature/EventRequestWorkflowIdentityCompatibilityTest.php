<?php

namespace Tests\Feature;

use App\Models\AnnualAgendaDeleteRequest;
use App\Models\AnnualAgendaEditRequest;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Modules\Events\Support\EventRequestModelIdentity;
use App\Services\AdminReports\AdminReportsService;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use ReflectionMethod;
use Tests\TestCase;

class EventRequestWorkflowIdentityCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_request_relationships_and_dynamic_resolution_accept_legacy_and_canonical_identities(): void
    {
        $workflow = $this->workflow('request_identity_test');
        $service = app(DynamicWorkflowService::class);
        $resolveEntity = new ReflectionMethod($service, 'resolveEntity');
        $resolveEntity->setAccessible(true);

        foreach ($this->requestCases() as $case) {
            $request = $this->request($case['class'], $case['request_type'], $case['aggregate_type']);
            $this->assertSame($case['aggregate_type'], $request->entity_type);

            foreach ([$case['legacy'], $case['canonical']] as $identity) {
                $instance = WorkflowInstance::query()->create([
                    'workflow_id' => $workflow->id,
                    'entity_type' => $identity,
                    'entity_id' => $request->id,
                    'status' => 'pending',
                    'started_at' => now(),
                ]);

                $request->unsetRelation('workflowInstance');
                $this->assertSame($instance->id, $request->workflowInstance?->id);

                $resolved = $resolveEntity->invoke($service, $instance);
                $this->assertInstanceOf($case['class'], $resolved);
                $this->assertSame($request->id, $resolved->id);

                $instance->delete();
            }
        }
    }

    public function test_for_model_reuses_either_identity_and_creates_only_the_legacy_identity(): void
    {
        $workflow = $this->workflow('monthly_activities');
        $request = $this->request(
            MonthlyPlanEditRequest::class,
            'edit',
            'App\\Models\\MonthlyActivity'
        );
        $service = app(DynamicWorkflowService::class);

        $legacy = WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'entity_type' => EventRequestModelIdentity::MONTHLY_EDIT_LEGACY,
            'entity_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertSame($legacy->id, $service->forModel('monthly_activities', $request)?->id);
        $this->assertSame(1, WorkflowInstance::query()->count());

        $legacy->delete();
        $canonical = WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'entity_type' => EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL,
            'entity_id' => $request->id,
            'status' => 'pending',
        ]);
        $this->assertSame($canonical->id, $service->forModel('monthly_activities', $request)?->id);
        $this->assertSame(1, WorkflowInstance::query()->count());

        $canonical->delete();
        $created = $service->forModel('monthly_activities', $request);
        $this->assertSame(EventRequestModelIdentity::MONTHLY_EDIT_LEGACY, $created?->entity_type);
        $this->assertNotSame(EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL, $created?->entity_type);
        $this->assertSame(1, WorkflowInstance::query()->count());
    }

    public function test_for_model_fails_when_both_request_identities_exist(): void
    {
        $workflow = $this->workflow('monthly_activities');
        $request = $this->request(
            MonthlyPlanDeleteRequest::class,
            'delete',
            'App\\Models\\MonthlyActivity'
        );

        foreach (EventRequestModelIdentity::acceptedTypes(MonthlyPlanDeleteRequest::class) as $identity) {
            WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => $request->id,
                'status' => 'pending',
            ]);
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Conflicting workflow identities exist');

        app(DynamicWorkflowService::class)->forModel('monthly_activities', $request);
    }

    public function test_admin_report_combines_legacy_and_canonical_monthly_request_identities(): void
    {
        $workflow = $this->workflow('monthly_activities');

        foreach ([
            [EventRequestModelIdentity::MONTHLY_EDIT_LEGACY, 1001],
            [EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL, 1002],
        ] as [$identity, $entityId]) {
            WorkflowInstance::query()->create([
                'workflow_id' => $workflow->id,
                'entity_type' => $identity,
                'entity_id' => $entityId,
                'status' => 'approved',
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);
        }

        $method = new ReflectionMethod(AdminReportsService::class, 'relationsReport');
        $method->setAccessible(true);
        $report = $method->invoke(app(AdminReportsService::class), (int) now()->year, (int) now()->month, null);
        $requestSpeed = $report['approval_speed']->firstWhere('module', 'MonthlyPlanEditRequest');

        $this->assertNotNull($requestSpeed);
        $this->assertSame(2, $requestSpeed['total']);
        $this->assertSame(1, $report['approval_speed']->where('module', 'MonthlyPlanEditRequest')->count());
    }

    /**
     * @return array<int, array{class: class-string<Model>, request_type: string, aggregate_type: string, legacy: string, canonical: string}>
     */
    private function requestCases(): array
    {
        return [
            ['class' => MonthlyPlanEditRequest::class, 'request_type' => 'edit', 'aggregate_type' => 'App\\Models\\MonthlyActivity', 'legacy' => EventRequestModelIdentity::MONTHLY_EDIT_LEGACY, 'canonical' => EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL],
            ['class' => MonthlyPlanDeleteRequest::class, 'request_type' => 'delete', 'aggregate_type' => 'App\\Models\\MonthlyActivity', 'legacy' => EventRequestModelIdentity::MONTHLY_DELETE_LEGACY, 'canonical' => EventRequestModelIdentity::MONTHLY_DELETE_CANONICAL],
            ['class' => AnnualAgendaEditRequest::class, 'request_type' => 'edit', 'aggregate_type' => 'App\\Modules\\Events\\Models\\AgendaEvent', 'legacy' => EventRequestModelIdentity::AGENDA_EDIT_LEGACY, 'canonical' => EventRequestModelIdentity::AGENDA_EDIT_CANONICAL],
            ['class' => AnnualAgendaDeleteRequest::class, 'request_type' => 'delete', 'aggregate_type' => 'App\\Modules\\Events\\Models\\AgendaEvent', 'legacy' => EventRequestModelIdentity::AGENDA_DELETE_LEGACY, 'canonical' => EventRequestModelIdentity::AGENDA_DELETE_CANONICAL],
        ];
    }

    /**
     * @param class-string<Model> $class
     */
    private function request(string $class, string $requestType, string $aggregateType): Model
    {
        return $class::query()->create([
            'requester_id' => User::factory()->create()->id,
            'request_type' => $requestType,
            'entity_type' => $aggregateType,
            'entity_id' => 999,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    private function workflow(string $module): Workflow
    {
        return Workflow::query()->create([
            'code' => $module . '_identity_test',
            'module' => $module,
            'name_ar' => 'اختبار الهوية',
            'name_en' => 'Identity test',
            'is_active' => true,
        ]);
    }
}
