<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExecutionNeedType;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Models\WorkflowInstance;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Services\RamadanIftarApprovalService;
use App\Modules\Events\Services\RamadanIftarSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanIftarApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->seed(WorkflowSeeder::class);
    }

    public function test_ready_draft_submits_with_independent_workflow_identity(): void
    {
        [$iftar, $officer] = $this->readyIftar();

        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);

        $iftar->refresh();
        $this->assertSame(RamadanIftar::STATUS_SUBMITTED, $iftar->status);
        $this->assertNotNull($iftar->submitted_at);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_PLANNED, $iftar->execution_status);
        $instance = WorkflowInstance::query()->sole();
        $this->assertSame(RamadanIftar::class, $instance->entity_type);
        $this->assertSame('ramadan_iftars', $instance->workflow->module);
        $this->assertSame('ramadan_supervisor_review', $instance->currentStep->step_key);
    }

    public function test_same_numeric_monthly_and_ramadan_ids_have_isolated_instances(): void
    {
        [$iftar, $officer] = $this->readyIftar();
        $monthlyWorkflow = app(\App\Services\DynamicWorkflowService::class)->findActiveWorkflow('monthly_activities');
        $monthlyInstance = app(\App\Services\DynamicWorkflowService::class)->forEntity($monthlyWorkflow, MonthlyActivity::class, $iftar->id);

        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);

        $this->assertCount(2, WorkflowInstance::all());
        $this->assertSame(MonthlyActivity::class, $monthlyInstance->fresh()->entity_type);
        $this->assertDatabaseHas('workflow_instances', ['entity_type' => RamadanIftar::class, 'entity_id' => $iftar->id]);
    }

    public function test_duplicate_submit_does_not_create_or_reset_workflow(): void
    {
        [$iftar, $officer] = $this->readyIftar();
        $service = app(RamadanIftarSubmissionService::class);
        $service->submit($iftar, $officer);
        $submittedAt = $iftar->fresh()->submitted_at;

        try {
            $service->submit($iftar->fresh(), $officer);
            $this->fail('Duplicate submission was accepted.');
        } catch (ValidationException $exception) {
            $this->assertCount(1, WorkflowInstance::all());
            $this->assertTrue($submittedAt->equalTo($iftar->fresh()->submitted_at));
        }
    }

    public function test_missing_guidance_or_incomplete_execution_need_decisions_blocks_submission(): void
    {
        [$iftar, $officer] = $this->readyIftar();
        $iftar->update(['guidance_version_id' => null, 'guidance_accepted_at' => null]);
        $this->expectException(ValidationException::class);
        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);
    }

    public function test_intermediate_approval_and_changes_requested_follow_generic_state(): void
    {
        [$iftar, $officer, $branch] = $this->readyIftar();
        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);
        $supervisor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $supervisor->assignRole('supervisor');
        $instance = $iftar->workflowInstance()->first();

        app(RamadanIftarApprovalService::class)->decide($iftar->fresh(), $supervisor, $instance->current_step_id, 'approved', null);
        $this->assertSame(RamadanIftar::STATUS_SUBMITTED, $iftar->fresh()->status);
        $this->assertNull($iftar->fresh()->approved_at);

        $coordinator = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $coordinator->assignRole('branch_coordinator');
        $instance->refresh();
        app(RamadanIftarApprovalService::class)->decide($iftar->fresh(), $coordinator, $instance->current_step_id, 'changes_requested', 'Correct the plan.');
        $this->assertSame(RamadanIftar::STATUS_CHANGES_REQUESTED, $iftar->fresh()->status);
        $this->assertTrue($iftar->fresh()->isPlanningEditable());
        $this->assertDatabaseHas('workflow_logs', ['comment' => 'Correct the plan.', 'action' => 'changes_requested']);
    }

    public function test_unknown_decision_is_rejected_without_audit_mutation(): void
    {
        [$iftar, $officer] = $this->readyIftar();
        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);

        try {
            app(RamadanIftarApprovalService::class)->decide($iftar->fresh(), $officer, 1, 'rejected', 'No');
            $this->fail('Reject was exposed to Ramadan.');
        } catch (ValidationException $exception) {
            $this->assertSame(1, WorkflowActionLog::query()->where('action_type', 'submitted')->count());
            $this->assertSame(RamadanIftar::STATUS_SUBMITTED, $iftar->fresh()->status);
        }
    }

    public function test_final_approval_sets_only_planning_approval_lifecycle(): void
    {
        [$iftar, $officer, $branch] = $this->readyIftar();
        app(RamadanIftarSubmissionService::class)->submit($iftar, $officer);
        $roles = ['supervisor', 'branch_coordinator', 'relations_manager', 'executive_manager'];

        foreach ($roles as $role) {
            $actor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
            $actor->assignRole($role);
            $instance = $iftar->workflowInstance()->first();
            app(RamadanIftarApprovalService::class)->decide($iftar->fresh(), $actor, $instance->current_step_id, 'approved', null);
        }

        $iftar->refresh();
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $iftar->status);
        $this->assertNotNull($iftar->approved_at);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_PLANNED, $iftar->execution_status);
        $this->assertNull($iftar->closed_at);
    }

    private function readyIftar(): array
    {
        $branch = Branch::factory()->create();
        $officer = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $officer->assignRole('relations_officer');
        $guidance = EventGuidanceVersion::query()->create([
            'code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => 1,
            'title' => 'Approved guidance', 'content' => 'Content', 'is_active' => true, 'published_at' => now(),
        ]);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id, 'title' => 'Iftar', 'relations_officer_id' => $officer->id,
            'created_by' => $officer->id, 'planned_date' => now()->addDay()->toDateString(),
            'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER, 'host_type' => RamadanIftar::HOST_CENTER,
            'planned_meals_count' => 0, 'expected_attendance' => 0, 'status' => RamadanIftar::STATUS_DRAFT,
            'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED, 'guidance_version_id' => $guidance->id,
            'guidance_accepted_at' => now(),
        ]);
        foreach (ExecutionNeedType::CANONICAL_DEFINITIONS as $code => $definition) {
            if (! $definition['ramadan']) continue;
            $type = ExecutionNeedType::query()->create(['code' => $code, 'name' => $definition['name'], 'is_active' => true, 'is_canonical' => true, 'is_ramadan_iftar' => true]);
            SubjectExecutionNeed::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'execution_need_type_id' => $type->id, 'is_required' => false]);
        }

        return [$iftar, $officer, $branch];
    }
}
